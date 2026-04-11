<script setup>
import { ref, onMounted, onBeforeUnmount, nextTick } from "vue";
import $ from "jquery";
import axios from "axios";
import Swal from "sweetalert2";

// ── Table ref ────────────────────────────────────────────────────
const tableRef = ref(null);

// ── Modal state ──────────────────────────────────────────────────
const showModal = ref(false);
const modalMode = ref("create");
const selectedRoleId = ref(null);
const form = ref({ name: "" });
const formErrors = ref({});
const isSaving = ref(false);

// ── Action column HTML ────────────────────────────────────────────
function actionFormatter(value, row) {
  return `
        <div class="d-flex gap-1 justify-content-center">
            <button class="bt-icon-btn bt-edit-btn"
                    data-id="${row.id}" data-name="${row.name}" title="Edit">
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
    $table.bootstrapTable("destroy");
  } catch (_) {}

  $table.bootstrapTable({
    url: "/api/roles",
    method: "get",
    classes: "table table-borderless",
    // Hand the left-side "Add Role" pill to Bootstrap Table's toolbar
    toolbar: "#roles-left-toolbar",
    pagination: true,
    sidePagination: "server",
    // Use Bootstrap Table's built-in search/refresh/columns
    search: true,
    checkboxHeader: true,
    clickToSelect: true,
    showRefresh: true,
    showColumns: true,
    showToggle: true,
    filterControl: false,
    sortName: "id",
    sortOrder: "desc",
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
          
        </div>
      `;
    },
    columns: [
      {
        field: "select",
        checkbox: true,
        align: "center",
        width: "40px",
        switchable: false,
        searchable: false,
        sortable: false,
      },
      {
        field: "id",
        title: "ID",
        sortable: true,
        align: "center",
        width: "80px",
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
        switchable: true,
      },
    ],
  });

  // Delegate action clicks
  $table.on("click", ".bt-edit-btn", function () {
    openEditModal({
      id: parseInt($(this).data("id")),
      name: String($(this).data("name")),
    });
  });

  $table.on("click", ".bt-delete-btn", function () {
    confirmDeleteRole({
      id: parseInt($(this).data("id")),
      name: String($(this).data("name")),
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
  selectedRoleId.value = null;
  form.value = { name: "" };
  formErrors.value = {};
  showModal.value = true;
}

function openEditModal(role) {
  modalMode.value = "edit";
  selectedRoleId.value = role.id;
  form.value = { name: role.name };
  formErrors.value = {};
  showModal.value = true;
}

function closeModal() {
  showModal.value = false;
  formErrors.value = {};
}

async function saveRole() {
  formErrors.value = {};
  if (!form.value.name.trim()) {
    formErrors.value = { name: "Role name is required." };
    return;
  }
  isSaving.value = true;
  try {
    if (modalMode.value === "create") {
      await axios.post("/roles", { name: form.value.name });
      window.toastr?.success("Role created successfully");
    } else {
      await axios.put(`/roles/${selectedRoleId.value}`, {
        name: form.value.name,
      });
      window.toastr?.success("Role updated successfully");
    }
    closeModal();
    refreshTable();
  } catch (err) {
    const errors = err.response?.data?.errors;
    if (errors) formErrors.value = errors;
    else window.toastr?.error("An error occurred. Please try again.");
  } finally {
    isSaving.value = false;
  }
}

async function confirmDeleteRole(role) {
  const result = await Swal.fire({
    title: "Delete role?",
    text: `Are you sure you want to delete "${role.name}"?`,
    icon: "warning",
    showCancelButton: true,
    confirmButtonText: "Yes, delete",
    confirmButtonColor: "#dc2626",
  });
  if (!result.isConfirmed) return;
  try {
    await axios.delete(`/roles/${role.id}`);
    window.toastr?.success("Role deleted successfully");
    refreshTable();
  } catch (_) {
    window.toastr?.error("Failed to delete role.");
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
    $(tableRef.value).off();
    $(tableRef.value).bootstrapTable("destroy");
  } catch (_) {}
});
</script>

<template>
  <div class="bt-outer">
    <!--
          Bootstrap Table picks up #roles-left-toolbar and injects it
          into the left (.bars) side of its generated toolbar.
        -->
    <div id="roles-left-toolbar" style="display: block">
      <button class="bt-pill-btn" @click="openCreateModal">Add Role</button>
    </div>

    <!-- Bootstrap Table target -->
    <table ref="tableRef"></table>
  </div>

  <!-- ── Role Modal ─────────────────────────────────────────────── -->
  <Teleport to="body">
    <div v-if="showModal" class="role-modal-overlay" @click.self="closeModal">
      <div class="role-modal-box">
        <div class="role-modal-header">
          <h5 class="role-modal-title">
            {{ modalMode === "create" ? "Add Role" : "Edit Role" }}
          </h5>
          <button class="btn-close" @click="closeModal"></button>
        </div>

        <div class="role-modal-body">
          <label class="form-label fw-semibold" for="role-name-input">
            Role Name
          </label>
          <input
            id="role-name-input"
            v-model="form.name"
            type="text"
            class="form-control"
            :class="{ 'is-invalid': formErrors.name }"
            placeholder="Enter role name"
            @keydown.enter="saveRole"
          />
          <div v-if="formErrors.name" class="invalid-feedback">
            {{
              Array.isArray(formErrors.name)
                ? formErrors.name[0]
                : formErrors.name
            }}
          </div>
        </div>

        <div class="role-modal-footer">
          <button class="btn btn-light" @click="closeModal">Cancel</button>
          <button
            class="btn btn-primary"
            :disabled="isSaving"
            @click="saveRole"
          >
            <span
              v-if="isSaving"
              class="spinner-border spinner-border-sm me-1"
            ></span>
            Save
          </button>
        </div>
      </div>
    </div>
  </Teleport>
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
   Bootstrap Table toolbar → gradient header
═══════════════════════════════════════════════════════════════ */
:deep(.fixed-table-toolbar) {
  /* background: linear-gradient(135deg, #3a5bd9 0%, #5b8df6 55%, #7ec8f8 100%); */
  background: linear-gradient(135deg, #1f326e 0%, #043399 55%, #1f326e 100%);
  padding: 14px 20px;
  display: flex !important;
  align-items: center;
  gap: 12px;
  flex-wrap: nowrap;
}

/* Left zone – "Add Role" pill injected via #roles-left-toolbar */
:deep(.fixed-table-toolbar .bars) {
  order: 1;
  flex: 0 0 auto;
  display: flex;
  align-items: center;
}

/* Search – grow to fill middle */
:deep(.fixed-table-toolbar .search) {
  order: 2;
  flex: 1 1 auto;
  display: flex;
  justify-content: center;
}

/* Right zone – refresh + columns → pushed to far right */
:deep(.fixed-table-toolbar .columns) {
  order: 3;
  flex: 0 0 auto;
  margin-left: auto;
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

/* Right zone – columns dropdown + refresh (display kept in sync with order rule above) */
:deep(.fixed-table-toolbar .columns) {
  display: flex;
  gap: 8px;
  align-items: center;
}

/* Every button in the toolbar → circular outline.
   !important is needed to override Bootstrap's .btn-group
   which flattens the inner-edge border-radius of grouped buttons. */
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

/* Dropdown caret – hide Bootstrap's default caret on the columns btn */
:deep(.fixed-table-toolbar .btn.dropdown-toggle::after) {
  display: none;
}

/* "Add Role" pill (wider, not circular) – overrides the generic .btn rule */
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

/* Sort icon colours */
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

/* Page number circles */
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

/* Rows-per-page select */
:deep(.fixed-table-pagination .page-size) {
  border-radius: 50px;
  border: 1.5px solid #dde3f0;
  padding: 4px 12px;
  font-size: 13px;
  color: #4a5568;
}

/* ═══════════════════════════════════════════════════════════════
   Action icon buttons inside cells
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
   Modal
═══════════════════════════════════════════════════════════════ */
.role-modal-overlay {
  position: fixed;
  inset: 0;
  background: rgba(0, 0, 0, 0.45);
  z-index: 1050;
  display: flex;
  align-items: center;
  justify-content: center;
}

.role-modal-box {
  background: #fff;
  border-radius: 18px;
  width: 440px;
  max-width: 95vw;
  box-shadow: 0 20px 60px rgba(0, 0, 0, 0.22);
}

.role-modal-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 1rem 1.25rem;
  border-bottom: 1px solid #eaecf6;
}

.role-modal-title {
  margin: 0;
  font-size: 1rem;
  font-weight: 700;
  color: #1f2937;
}

.role-modal-body {
  padding: 1.25rem;
}

.role-modal-footer {
  padding: 1rem 1.25rem;
  border-top: 1px solid #eaecf6;
  display: flex;
  justify-content: flex-end;
  gap: 0.5rem;
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
