<script setup>
import { ref, onMounted, onBeforeUnmount, nextTick } from "vue";

import $ from "jquery";
import Swal from "sweetalert2";
import axios from "axios";
import Breadcrumb from "primevue/breadcrumb";
import FaFormModal from "./FaFormModal.vue";

// ── Table ref ─────────────────────────────────────────────────────
const tableRef = ref(null);

// ── Breadcrumb data ─────────────────────────────────────────────
const home = ref({
  icon: "pi pi-home",
  url: route("home"),
});

const items = ref([
  { label: "Functional Areas", url: "/functional-areas", icon: "pi pi-map-marker" },
]);

// ── Modal state ───────────────────────────────────────────────────
const showModal = ref(false);
const modalMode = ref("create");
const selectedFunctionalArea = ref(null);

// Returns a fixed height on desktop; undefined on mobile (Bootstrap Table ignores undefined)
function getTableHeight() {
  return window.innerWidth < 768
    ? undefined
    : Math.max(300, window.innerHeight - 210);
}

function statusFormatter(_value, row) {
  if (!row.status_name) return '<span class="text-muted">—</span>';
  const cls = `ev-status-${row.status_color || "secondary"}`;
  return `<span class="ev-status-badge ${cls}">${row.status_name}</span>`;
}

function actionFormatter(_value, row) {
  return `
    <div class="d-flex gap-1 justify-content-center">
      <button class="bt-icon-btn bt-edit-btn"
              data-id="${row.id}"
              data-title="${row.title}"
              data-flag="${row.active_flag}"
              title="Edit">
        <i class="fa fa-pencil-alt"></i>
      </button>
      <button class="bt-icon-btn bt-delete-btn"
              data-id="${row.id}" data-title="${row.title}" title="Delete">
        <i class="fa fa-trash"></i>
      </button>
    </div>`;
}

// ── Bootstrap Table init ──────────────────────────────────────────
function initTable() {
  if (!tableRef.value) return;

  const $table = $(tableRef.value);
  try {
    $table.bootstrapTable("destroy");
  } catch (_) {}

  $table.bootstrapTable({
    url: route("functional-areas.data"),
    method: "get",
    toolbar: "#functional-areas-left-toolbar",
    classes: "table table-borderless",
    pagination: true,
    sidePagination: "server",
    search: true,
    // height: undefined,
    height: getTableHeight(),
    showRefresh: true,
    showToggle: true,
    showColumns: true,
    sortName: "id",
    sortOrder: "desc",
    pageList: [5, 10, 25, 50],
    pageSize: 10,
    iconsPrefix: "fa",
    icons: { refresh: "fa-sync", columns: "fa-list-ul" },
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
      return `<div class="text-center py-5">
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
        field: "title",
        title: "Title",
        sortable: true,
        switchable: true,
        formatter: (_v, row) =>
          `<span class="fw-semibold text-dark">${row.title ?? "—"}</span>`,
      },
      {
        field: "status_name",
        title: "Status",
        sortable: true,
        switchable: true,
        formatter: statusFormatter,
      },
      {
        field: "created_at",
        title: "Created",
        sortable: true,
        switchable: true,
        visible: false,
        formatter: (v) => v ?? "—",
      },
      {
        field: "updated_at",
        title: "Updated",
        sortable: true,
        switchable: true,
        visible: false,
        formatter: (v) => v ?? "—",
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

  $table.on("click", ".bt-edit-btn", function () {
    const $btn = $(this);
    openEditModal({
      id: parseInt($btn.data("id")),
      title: String($btn.data("title")),
      active_flag: $btn.data("flag") ?? "",
    });
  });

  $table.on("click", ".bt-delete-btn", function () {
    confirmDelete({
      id: parseInt($(this).data("id")),
      title: String($(this).data("title")),
    });
  });
}

function refreshTable() {
  if (!tableRef.value) return;
  $(tableRef.value).bootstrapTable("refresh");
}

// ── Modal ─────────────────────────────────────────────────────────
function openCreateModal() {
  modalMode.value = "create";
  selectedFunctionalArea.value = null;
  showModal.value = true;
}

function openEditModal(functionalArea) {
  modalMode.value = "edit";
  selectedFunctionalArea.value = functionalArea;
  showModal.value = true;
}

function closeModal() {
  showModal.value = false;
  selectedFunctionalArea.value = null;
}

// ── Delete ────────────────────────────────────────────────────────
async function confirmDelete({ id, title }) {
  const result = await Swal.fire({
    title: "Delete functional area?",
    text: `Are you sure you want to delete "${title}"?`,
    icon: "warning",
    showCancelButton: true,
    confirmButtonText: "Yes, delete",
    confirmButtonColor: "#dc2626",
  });
  if (!result.isConfirmed) return;
  try {
    await axios.delete(route("functional-areas.destroy", id));
    window.toastr?.success("Functional area deleted successfully");
    refreshTable();
  } catch (_) {
    window.toastr?.error("Failed to delete functional area.");
  }
}

// ── Lifecycle ─────────────────────────────────────────────────────
function onResize() {
  if (!tableRef.value) return;
  try {
    $(tableRef.value).bootstrapTable("resetView", { height: getTableHeight() });
  } catch (_) {}
}

onMounted(async () => {
  await nextTick();
  initTable();
  window.addEventListener("resize", onResize);
});

onBeforeUnmount(() => {
  window.removeEventListener("resize", onResize);
  if (!tableRef.value) return;
  try {
    $(tableRef.value).off();
    $(tableRef.value).bootstrapTable("destroy");
  } catch (_) {}
});
</script>

<template>
  <div class="d-flex align-items-center gap-2 flex-wrap mb-4">
    <Breadcrumb :home="home" :model="items" />
  </div>
  <div class="bt-outer">
    <div id="functional-areas-left-toolbar" style="display: block">
      <button class="bt-pill-btn" @click="openCreateModal">
        <i class="fa fa-plus me-1"></i> Add Functional Area
      </button>
    </div>

    <table ref="tableRef" class="table table-responsive"></table>
  </div>

  <FaFormModal
    :show="showModal"
    :mode="modalMode"
    :fa="selectedFunctionalArea"
    @close="closeModal"
    @saved="
      () => {
        closeModal();
        refreshTable();
      }
    "
  />
</template>

<style scoped>
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

.bt-outer {
  background: #fff;
  border-radius: 18px;
  overflow: hidden;
  box-shadow: 0 4px 28px rgba(60, 80, 200, 0.13);
  border: 1px solid #dde3f0;
}

/* :deep(.fixed-table-toolbar) {
  background: linear-gradient(135deg, #1f326e 0%, #043399 55%, #1f326e 100%);
  padding: 14px 20px;
  display: flex !important;
  align-items: center;
  gap: 12px;
  flex-wrap: nowrap;
} */

:deep(.fixed-table-toolbar .bars) {
  order: 1;
  flex: 0 0 auto;
  display: flex;
  align-items: center;
}

:deep(.fixed-table-toolbar .search) {
  order: 2;
  flex: 1 1 auto;
  display: flex;
  justify-content: center;
}

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

:deep(.fixed-table-toolbar .bars .bt-pill-btn),
.bt-pill-btn {
  width: auto !important;
  height: 38px;
  padding: 0 18px !important;
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
  white-space: nowrap;
}

:deep(.fixed-table-toolbar .bars .bt-pill-btn:hover),
.bt-pill-btn:hover {
  background: rgba(255, 255, 255, 0.18) !important;
}

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
  background: transparent !important;
  margin: 0 2px;
  transition: background 0.15s;
}

/* :deep(.fixed-table-pagination .pagination .page-item.active .page-link) {
  background: #3a5bd9 !important;
  color: #fff !important;
  border-radius: 50% !important;
  border: none;
} */

/* :deep(
    .fixed-table-pagination .pagination .page-item:hover:not(.active) .page-link
  ) {
  background: transparent;
}

:deep(.fixed-table-pagination .page-size) {
  border-radius: 50px;
  border: 1.5px solid var(--pagination-active-bg);
  padding: 4px 12px;
  font-size: 13px;
  color: #fff;
  background-color: var(--pagination-active-bg);
} */

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

:deep(.ev-status-badge) {
  display: inline-flex;
  align-items: center;
  padding: 3px 12px;
  border-radius: 50px;
  font-size: 12px;
  font-weight: 600;
  border: 1px solid;
}

:deep(.ev-status-success) {
  background: #dcfce7;
  color: #16a34a;
  border-color: #bbf7d0;
}
:deep(.ev-status-danger) {
  background: #fee2e2;
  color: #dc2626;
  border-color: #fecaca;
}
:deep(.ev-status-warning) {
  background: #fef9c3;
  color: #ca8a04;
  border-color: #fde68a;
}
:deep(.ev-status-info) {
  background: #dbeafe;
  color: #2563eb;
  border-color: #bfdbfe;
}
:deep(.ev-status-secondary) {
  background: #f1f5f9;
  color: #64748b;
  border-color: #e2e8f0;
}

:deep(.ev-logo-img) {
  width: 44px;
  height: 44px;
  object-fit: contain;
  border-radius: 8px;
  border: 1px solid #eaecf6;
  background: #f8f9ff;
  padding: 2px;
}

:deep(.ev-logo-placeholder) {
  width: 44px;
  height: 44px;
  border-radius: 8px;
  border: 1px solid #eaecf6;
  background: #f8f9ff;
  display: flex;
  align-items: center;
  justify-content: center;
  color: #c7d2fe;
  font-size: 18px;
  margin: 0 auto;
}

@media (max-width: 640px) {
  :deep(.fixed-table-toolbar) {
    flex-wrap: wrap;
  }
  :deep(.fixed-table-toolbar .search input.form-control) {
    min-width: 160px;
  }
}
</style>
