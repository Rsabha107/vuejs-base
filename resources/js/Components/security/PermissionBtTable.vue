<script setup>
import { ref, onMounted, onBeforeUnmount, nextTick } from "vue";
import $ from "jquery";
import axios from "axios";
import Swal from "sweetalert2";
import Breadcrumb from "primevue/breadcrumb";
import PermissionModal from "./PermissionFormModal.vue";

// ── Table ref ────────────────────────────────────────────────────
const tableRef = ref(null);

// ── Breadcrumb data ─────────────────────────────────────────────
const home = ref({
  icon: "pi pi-home",
  url: route("home"),
});

const items = ref([
  { label: "Permissions", url: "/permissions", icon: "pi pi-lock" },
]);
// ── Modal state ──────────────────────────────────────────────────
const showPermissionModal = ref(false);
const modalMode = ref("create");
const selectedPermission = ref(null);

// ── Action column HTML ────────────────────────────────────────────
function actionFormatter(_value, row) {
  return `
        <div class="d-flex gap-1 justify-content-center">
            <button class="bt-icon-btn bt-edit-btn"
                    data-id="${row.id}" title="Edit">
                <i class="fa fa-pencil-alt"></i>
            </button>
            <button class="bt-icon-btn bt-delete-btn"
                    data-id="${row.id}" data-name="${row.name}" title="Delete">
                <i class="fa fa-trash"></i>
            </button>
        </div>`;
}

// ── Bootstrap Table init ──────────────────────────────────────────
function initTable() {
  if (!tableRef.value) return;

  const $table = $(tableRef.value);
  try {
    $table.off(".permissionActions");
    $table.bootstrapTable("destroy");
  } catch (_) {}

  $table.bootstrapTable({
    url: route("permissions.data"),
    method: "get",
    toolbar: "#permissions-left-toolbar",
    pagination: true,
    classes: "table table-borderless",
    sidePagination: "server",
    showToggle: true,
    search: true,
    showRefresh: true,
    showColumns: true,
    filterControl: false,
    sortName: "id",
    sortOrder: "asc",
    pageList: [5, 10, 25, 50],
    pageSize: 10,
    iconsPrefix: "fa",
    icons: {
      refresh: "fa-sync",
      columns: "fa-list-ul",
    },
    queryParams(params) {
      return {
        limit: params.limit,
        offset: params.offset,
        search: params.search,
        sort: params.sort,
        order: params.order,
      };
    },
    loadingTemplate() {
      return `
                <div class="text-center py-5">
                    <div class="spinner-border text-primary mb-3" role="status"></div>
                </div>`;
    },
    columns: [
      {
        field: "id",
        title: "ID",
        sortable: true,
        align: "center",
        width: "80px",
        visible: false,
        switchable: true,
      },
      {
        field: "name",
        title: "Name",
        sortable: true,
        switchable: true,
      },
      {
        field: "actions",
        title: "Actions",
        align: "center",
        width: "120px",
        formatter: actionFormatter,
        searchable: false,
        sortable: false,
        switchable: false,
      },
    ],
  });

  // Delegate action clicks
  $table.on("click.permissionActions", ".bt-edit-btn", async function () {
    const id = parseInt($(this).data("id"));
    try {
      const { data } = await axios.get(route("permissions.show", id));
      modalMode.value = "edit";
      selectedPermission.value = data;
      showPermissionModal.value = true;
    } catch (_) {
      window.toastr?.error("Could not load permission details.");
    }
  });

  $table.on("click.permissionActions", ".bt-delete-btn", function () {
    const id = parseInt($(this).data("id"));
    const name = String($(this).data("name"));
    confirmDelete({ id, name });
  });
}

function refreshTable() {
  if (!tableRef.value) return;
  $(tableRef.value).bootstrapTable("refresh");
}

// ── Modal helpers ─────────────────────────────────────────────────
function openCreateModal() {
  modalMode.value = "create";
  selectedPermission.value = null;
  showPermissionModal.value = true;
}

function closePermissionModal() {
  showPermissionModal.value = false;
  selectedPermission.value = null;
}

function handleSaved() {
  closePermissionModal();
  refreshTable();
}

// ── Delete ────────────────────────────────────────────────────────
async function confirmDelete({ id, name }) {
  const result = await Swal.fire({
    title: "Delete permission?",
    text: `Are you sure you want to delete "${name}"?`,
    icon: "warning",
    showCancelButton: true,
    confirmButtonText: "Yes, delete",
    confirmButtonColor: "#dc2626",
  });
  if (!result.isConfirmed) return;
  try {
    await axios.delete(route("permissions.destroy", id));
    window.toastr?.success("Permission deleted successfully");
    refreshTable();
  } catch (_) {
    window.toastr?.error("Failed to delete permission.");
  }
}

// ── Lifecycle ─────────────────────────────────────────────────────
onMounted(async () => {
  await nextTick();
  initTable();
});

onBeforeUnmount(() => {
  if (!tableRef.value) return;
  try {
    $(tableRef.value).off(".permissionActions");
    $(tableRef.value).bootstrapTable("destroy");
  } catch (_) {}
});
</script>

<template>
  <div class="d-flex align-items-center gap-2 flex-wrap mb-4">
    <Breadcrumb :home="home" :model="items" />
  </div>
  <div class="bt-outer">
    <!-- Bootstrap Table injects this into the left (.bars) toolbar slot -->
    <div id="permissions-left-toolbar" style="display: block">
      <button class="bt-pill-btn" @click="openCreateModal">
        Add Permission
      </button>
    </div>

    <table ref="tableRef" class="table table-responsive"></table>
  </div>

  <PermissionModal
    :show="showPermissionModal"
    :mode="modalMode"
    :permission="selectedPermission"
    @close="closePermissionModal"
    @saved="handleSaved"
  />
</template>

<style scoped>
/* ═══════════════════════════════════════════════════════════════
   Outer card
═══════════════════════════════════════════════════════════════ */
.bt-outer {
  background: #fff;
  border-radius: 18px;
  overflow: hidden;
  box-shadow: 0 4px 28px rgba(60, 80, 200, 0.13);
  border: 1px solid #dde3f0;
}

/* ═══════════════════════════════════════════════════════════════
   Transparent table backgrounds
═══════════════════════════════════════════════════════════════ */
:deep(table),
:deep(.fixed-table-container),
:deep(.fixed-table-body),
:deep(.fixed-table-header),
:deep(thead th),
:deep(tbody td),
:deep(tbody tr) {
  background-color: transparent !important;
  --bs-table-bg: transparent;
}

/* ═══════════════════════════════════════════════════════════════
   Toolbar – gradient header
═══════════════════════════════════════════════════════════════ */
:deep(.fixed-table-toolbar) {
  background: linear-gradient(135deg, #1f326e 0%, #043399 55%, #1f326e 100%);
  /* background: linear-gradient(135deg, #3a5bd9 0%, #5b8df6 55%, #7ec8f8 100%); */
  padding: 14px 20px;
  display: flex !important;
  align-items: center;
  gap: 12px;
  flex-wrap: nowrap;
}

/* Left: "Add Permission" pill */
:deep(.fixed-table-toolbar .bars) {
  order: 1;
  flex: 0 0 auto;
  display: flex;
  align-items: center;
}

/* Centre: search */
:deep(.fixed-table-toolbar .search) {
  order: 2;
  flex: 1 1 auto;
  display: flex;
  justify-content: center;
}

/* Right: refresh + columns */
:deep(.fixed-table-toolbar .columns) {
  order: 3;
  flex: 0 0 auto;
  margin-left: auto;
  display: flex;
  gap: 8px;
  align-items: center;
}

:deep(.fixed-table-toolbar .search input.form-control) {
  border-radius: 50px;
  border: 1.5px solid rgba(255, 255, 255, 0.55);
  background: rgba(255, 255, 255, 0.18);
  color: #fff;
  padding: 6px 20px;
  min-width: 220px;
  max-width: 340px;
  backdrop-filter: blur(4px);
  transition: border-color 0.2s, background 0.2s;
}

:deep(.fixed-table-toolbar .search input.form-control::placeholder) {
  color: rgba(255, 255, 255, 0.65);
}

:deep(.fixed-table-toolbar .search input.form-control:focus) {
  background: rgba(255, 255, 255, 0.28);
  border-color: rgba(255, 255, 255, 0.9);
  box-shadow: none;
  color: #fff;
  outline: none;
}

/* Circular icon buttons – !important overrides Bootstrap's .btn-group radius reset */
:deep(.fixed-table-toolbar .btn) {
  width: 38px;
  height: 38px;
  padding: 0;
  border-radius: 50% !important;
  border: 1.5px solid rgba(255, 255, 255, 0.55);
  background: transparent;
  color: #fff;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  transition: background 0.18s, border-color 0.18s;
}

:deep(.fixed-table-toolbar .btn:hover),
:deep(.fixed-table-toolbar .btn:focus) {
  background: rgba(255, 255, 255, 0.18);
  border-color: rgba(255, 255, 255, 0.9);
  color: #fff;
  box-shadow: none;
}

:deep(.fixed-table-toolbar .btn.dropdown-toggle::after) {
  display: none;
}

/* "Add Permission" pill – wider than circular buttons */
:deep(.fixed-table-toolbar .bars .bt-pill-btn),
.bt-pill-btn {
  width: auto !important;
  height: 38px;
  padding: 0 20px !important;
  border-radius: 50px !important;
  border: 1.5px solid rgba(255, 255, 255, 0.7) !important;
  background: transparent !important;
  color: #fff !important;
  font-size: 14px;
  font-weight: 500;
  cursor: pointer;
  display: inline-flex;
  align-items: center;
  gap: 6px;
  transition: background 0.18s;
}

:deep(.fixed-table-toolbar .bars .bt-pill-btn:hover),
.bt-pill-btn:hover {
  background: rgba(255, 255, 255, 0.18) !important;
}

/* ═══════════════════════════════════════════════════════════════
   Dark column-visibility dropdown
═══════════════════════════════════════════════════════════════ */
:deep(.fixed-table-toolbar .dropdown-menu) {
  background: #2a3250 !important;
  border: none !important;
  border-radius: 14px !important;
  padding: 14px 16px !important;
  box-shadow: 0 8px 32px rgba(0, 0, 0, 0.35) !important;
  min-width: 160px;
}

:deep(.fixed-table-toolbar .dropdown-item),
:deep(.fixed-table-toolbar .dropdown-menu label) {
  color: #e8edf8 !important;
  font-size: 13px;
  font-weight: 600;
  letter-spacing: 0.04em;
  text-transform: uppercase;
  display: flex;
  align-items: center;
  gap: 10px;
  cursor: pointer;
  padding: 6px 0;
  background: transparent !important;
}

:deep(.fixed-table-toolbar .dropdown-menu .form-check-input) {
  border-radius: 4px;
  border-color: #5b8df6;
}

:deep(.fixed-table-toolbar .dropdown-menu .form-check-input:checked) {
  background-color: #5b8df6;
  border-color: #5b8df6;
}

/* ═══════════════════════════════════════════════════════════════
   Table head
═══════════════════════════════════════════════════════════════ */
:deep(thead th) {
  background: #fff !important;
  color: #1a1a2e;
  font-size: 12px;
  font-weight: 700;
  letter-spacing: 0.06em;
  text-transform: uppercase;
  border-bottom: 2px solid #eaecf6;
  padding: 13px 16px;
}

:deep(.sortable .both),
:deep(.sortable .asc),
:deep(.sortable .desc) {
  color: #8899bb;
}

/* ═══════════════════════════════════════════════════════════════
   Table body
═══════════════════════════════════════════════════════════════ */
:deep(table) {
  margin-bottom: 0 !important;
}

:deep(tbody td) {
  vertical-align: middle;
  font-size: 14px;
  padding: 12px 16px;
  color: #4a5568;
  border-bottom: 1px solid #f2f4fb;
}

:deep(tbody tr:hover td) {
  background: #f5f7ff !important;
}

/* ═══════════════════════════════════════════════════════════════
   Pagination
═══════════════════════════════════════════════════════════════ */
:deep(.fixed-table-pagination) {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 12px 20px;
  border-top: 1px solid #eaecf6;
}

:deep(.fixed-table-pagination .pagination .page-link) {
  border: none;
  border-radius: 50% !important;
  width: 32px;
  height: 32px;
  padding: 0;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 13px;
  color: #4a5568;
  background: transparent;
  margin: 0 2px;
  transition: background 0.15s;
}

:deep(.fixed-table-pagination .pagination .page-item.active .page-link) {
  background: #3a5bd9 !important;
  color: #fff !important;
  border-radius: 50% !important;
  border: none;
}

:deep(
    .fixed-table-pagination .pagination .page-item:hover:not(.active) .page-link
  ) {
  background: #eaecf6;
}

:deep(.fixed-table-pagination .page-size) {
  border-radius: 50px;
  border: 1.5px solid #dde3f0;
  padding: 4px 12px;
  font-size: 13px;
  color: #4a5568;
}

/* ═══════════════════════════════════════════════════════════════
   Action icon buttons
═══════════════════════════════════════════════════════════════ */
:deep(.bt-icon-btn) {
  width: 34px;
  height: 34px;
  border-radius: 50%;
  border: 1px solid transparent;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  padding: 0;
  cursor: pointer;
  transition: background 0.15s;
}

:deep(.bt-edit-btn) {
  background: #fff7e6;
  border-color: #fde7b0;
  color: #d97706;
}
:deep(.bt-edit-btn:hover) {
  background: #fef3c7;
}

:deep(.bt-delete-btn) {
  background: #fff1f2;
  border-color: #fecdd3;
  color: #dc2626;
}
:deep(.bt-delete-btn:hover) {
  background: #ffe4e6;
}

/* ═══════════════════════════════════════════════════════════════
   Responsive
═══════════════════════════════════════════════════════════════ */
@media (max-width: 640px) {
  :deep(.fixed-table-toolbar) {
    flex-wrap: wrap;
  }
  :deep(.fixed-table-toolbar .search input.form-control) {
    min-width: 160px;
  }
}
</style>
