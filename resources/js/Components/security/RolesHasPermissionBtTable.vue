<script setup>
import { ref, computed, onMounted, onBeforeUnmount, nextTick } from "vue";
import $ from "jquery";
import axios from "axios";
import Breadcrumb from "primevue/breadcrumb";
import Swal from "sweetalert2";

// ── Table ref ────────────────────────────────────────────────────
const tableRef = ref(null);

const home = { icon: "pi pi-home", url: "/" };
const items = ref([
  {
    label: "Roles & Permissions",
    url: "/roles-permissions",
    icon: "pi pi-shield",
  },
]);

// ── Modal state ──────────────────────────────────────────────────
const showModal = ref(false);
const modalMode = ref("create"); // "create" | "edit"
const isSaving = ref(false);
const isLoadingModal = ref(false);

const form = ref({
  role_id: "",
  role_name: "",
  permission_ids: [],
});
const formErrors = ref({});

// ── Data for modal dropdowns ──────────────────────────────────────
const allRoles = ref([]);
const allPermissions = ref([]);
const permSearch = ref("");

const filteredPermissions = computed(() =>
  permSearch.value.trim()
    ? allPermissions.value.filter((p) =>
        p.name.toLowerCase().includes(permSearch.value.toLowerCase())
      )
    : allPermissions.value
);

// ── Action column HTML ────────────────────────────────────────────
function actionFormatter(_value, row) {
  return `
        <div class="d-flex gap-1 justify-content-center">
            <button class="bt-icon-btn bt-edit-btn"
                    data-id="${row.id}" data-name="${row.name}" title="Edit permissions">
                <i class="fa fa-pencil-alt"></i>
            </button>
            <button class="bt-icon-btn bt-delete-btn"
                    data-id="${row.id}" data-name="${row.name}" title="Delete role">
                <i class="fa fa-trash"></i>
            </button>
        </div>`;
}

function permissionsFormatter(value) {
  if (!value || value.length === 0) {
    return '<span class="text-muted fst-italic small">No permissions</span>';
  }
  return value.map((p) => `<span class="perm-badge">${p.name}</span>`).join("");
}

// ── Bootstrap Table init ──────────────────────────────────────────
function initTable() {
  if (!tableRef.value) return;

  const $table = $(tableRef.value);
  try {
    $table.bootstrapTable("destroy");
  } catch (_) {}

  $table.bootstrapTable({
    url: "/api/roles-permissions",
    method: "get",
    toolbar: "#rp-left-toolbar",
    classes: "table table-borderless",
    pagination: true,
    sidePagination: "server",
    search: true,
    showRefresh: true,
    showToggle: true,
    showColumns: true,
    sortName: "id",
    sortOrder: "asc",
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
      { field: "name", title: "Name", sortable: true, switchable: true },
      {
        field: "permissions",
        title: "Permissions",
        sortable: false,
        searchable: false,
        switchable: true,
        formatter: permissionsFormatter,
      },
      {
        field: "actions",
        title: "Actions",
        sortable: false,
        searchable: false,
        switchable: false,
        align: "center",
        width: "120px",
        formatter: actionFormatter,
      },
    ],
  });

  // Edit → open assign modal pre-filled with role's current permissions
  $table.on("click", ".bt-edit-btn", async function () {
    const id = parseInt($(this).data("id"));
    const name = String($(this).data("name"));
    await openEditModal(id, name);
  });

  // Delete → confirm then destroy
  $table.on("click", ".bt-delete-btn", function () {
    confirmDelete({
      id: parseInt($(this).data("id")),
      name: String($(this).data("name")),
    });
  });
}

function refreshTable() {
  if (!tableRef.value) return;
  $(tableRef.value).bootstrapTable("refresh");
}

// ── Load dropdown data ────────────────────────────────────────────
async function loadModalData() {
  if (allPermissions.value.length && allRoles.value.length) return; // cached

  const [permsRes, rolesRes] = await Promise.all([
    axios.get("/api/roles-permissions/all-permissions"),
    axios.get("/api/roles-permissions/all-roles"),
  ]);
  allPermissions.value = permsRes.data;
  allRoles.value = rolesRes.data;
}

// ── Modal helpers ─────────────────────────────────────────────────
async function openCreateModal() {
  isLoadingModal.value = true;
  await loadModalData();
  isLoadingModal.value = false;

  modalMode.value = "create";
  permSearch.value = "";
  form.value = { role_id: "", role_name: "", permission_ids: [] };
  formErrors.value = {};
  showModal.value = true;
}

async function openEditModal(id, name) {
  isLoadingModal.value = true;
  await loadModalData();

  // Fetch current permission IDs for this role
  const { data } = await axios.get(
    `/api/roles-permissions?limit=1&offset=0&search=${encodeURIComponent(name)}`
  );
  const row = data.rows?.find((r) => r.id === id);
  const currentPermIds = row ? row.permissions.map((p) => p.id) : [];

  isLoadingModal.value = false;

  modalMode.value = "edit";
  permSearch.value = "";
  form.value = { role_id: id, role_name: name, permission_ids: currentPermIds };
  formErrors.value = {};
  showModal.value = true;
}

function closeModal() {
  showModal.value = false;
  formErrors.value = {};
}

// ── Save ──────────────────────────────────────────────────────────
async function saveAssignment() {
  formErrors.value = {};

  if (modalMode.value === "create" && !form.value.role_id) {
    formErrors.value.role_id = "Please select a role.";
    return;
  }

  isSaving.value = true;
  try {
    await axios.put(`/api/roles-permissions/${form.value.role_id}`, {
      permission_ids: form.value.permission_ids,
    });
    window.toastr?.success("Permissions saved successfully");
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

// ── Delete ────────────────────────────────────────────────────────
async function confirmDelete({ id, name }) {
  const result = await Swal.fire({
    title: "Delete role?",
    text: `Are you sure you want to delete "${name}"? This will remove all its permissions.`,
    icon: "warning",
    showCancelButton: true,
    confirmButtonText: "Yes, delete",
    confirmButtonColor: "#dc2626",
  });
  if (!result.isConfirmed) return;
  try {
    await axios.delete(`/api/roles-permissions/${id}`);
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
  <div class="d-flex align-items-center gap-2 flex-wrap mb-4">
    <Breadcrumb :home="home" :model="items" />
  </div>

  <div class="bt-outer">
    <!-- Left toolbar pill injected by Bootstrap Table -->
    <div id="rp-left-toolbar" style="display: block">
      <button
        class="bt-pill-btn"
        :disabled="isLoadingModal"
        @click="openCreateModal"
      >
        <span
          v-if="isLoadingModal"
          class="spinner-border spinner-border-sm me-1"
        ></span>
        <i v-else class="fa fa-key me-1"></i>
        Assign Permissions to Role
      </button>
    </div>

    <table ref="tableRef" class="table table-responsive"></table>
  </div>

  <!-- ── Assign / Edit Modal ────────────────────────────────────── -->
  <Teleport to="body">
    <div v-if="showModal" class="rp-modal-overlay" @click.self="closeModal">
      <div class="rp-modal-box">
        <!-- Header -->
        <div class="rp-modal-header">
          <h5 class="rp-modal-title">
            <i class="fa fa-key me-2 text-primary"></i>
            {{
              modalMode === "create"
                ? "Assign Permissions to Role"
                : "Edit Role Permissions"
            }}
          </h5>
          <button class="btn-close" @click="closeModal"></button>
        </div>

        <!-- Body -->
        <div class="rp-modal-body">
          <!-- Role selector (create mode) -->
          <div v-if="modalMode === 'create'" class="mb-4">
            <label class="form-label fw-semibold">Role</label>
            <select
              v-model="form.role_id"
              class="form-select"
              :class="{ 'is-invalid': formErrors.role_id }"
            >
              <option value="">Select a role…</option>
              <option v-for="role in allRoles" :key="role.id" :value="role.id">
                {{ role.name }}
              </option>
            </select>
            <div v-if="formErrors.role_id" class="invalid-feedback">
              {{ formErrors.role_id }}
            </div>
          </div>

          <!-- Role label (edit mode) -->
          <div v-else class="mb-4">
            <label class="form-label fw-semibold">Role</label>
            <div class="role-label">{{ form.role_name }}</div>
          </div>

          <!-- Permissions -->
          <div>
            <div class="d-flex justify-content-between align-items-center mb-2">
              <label class="form-label fw-semibold mb-0">
                Permissions
                <span class="badge-count"
                  >{{ form.permission_ids.length }} selected</span
                >
              </label>
              <button
                type="button"
                class="btn btn-link btn-sm p-0 text-muted text-decoration-none"
                @click="form.permission_ids = []"
              >
                Clear all
              </button>
            </div>

            <!-- Search -->
            <input
              v-model="permSearch"
              type="text"
              class="form-control form-control-sm mb-2"
              placeholder="Search permissions…"
            />

            <!-- Checkbox list -->
            <div class="perm-list">
              <label
                v-for="perm in filteredPermissions"
                :key="perm.id"
                class="perm-check-item"
                :class="{ 'is-checked': form.permission_ids.includes(perm.id) }"
              >
                <input
                  type="checkbox"
                  :value="perm.id"
                  v-model="form.permission_ids"
                  class="perm-checkbox"
                />
                <span>{{ perm.name }}</span>
              </label>

              <div
                v-if="filteredPermissions.length === 0"
                class="text-muted small py-3 text-center"
              >
                No permissions found.
              </div>
            </div>
          </div>
        </div>

        <!-- Footer -->
        <div class="rp-modal-footer">
          <button class="btn btn-light" @click="closeModal">Cancel</button>
          <button
            class="btn btn-primary"
            :disabled="isSaving"
            @click="saveAssignment"
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
  /* background: linear-gradient(135deg, #3a5bd9 0%, #5b8df6 55%, #7ec8f8 100%); */
  background: linear-gradient(135deg, #1f326e 0%, #043399 55%, #1f326e 100%);
  padding: 14px 20px;
  display: flex !important;
  align-items: center;
  gap: 12px;
  flex-wrap: nowrap;
}

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

/* "Assign Permissions" pill */
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
   Table head / body
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
   Permission badges (inside table cells)
═══════════════════════════════════════════════════════════════ */
:deep(.perm-badge) {
  display: inline-flex;
  align-items: center;
  padding: 2px 10px;
  border-radius: 50px;
  font-size: 12px;
  font-weight: 600;
  background: #eef2ff;
  color: #3a5bd9;
  border: 1px solid #c7d2fe;
  margin: 2px 3px 2px 0;
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
   Modal
═══════════════════════════════════════════════════════════════ */
.rp-modal-overlay {
  position: fixed;
  inset: 0;
  background: rgba(0, 0, 0, 0.45);
  z-index: 1050;
  display: flex;
  align-items: center;
  justify-content: center;
}

.rp-modal-box {
  background: #fff;
  border-radius: 18px;
  width: 520px;
  max-width: 95vw;
  max-height: 90vh;
  display: flex;
  flex-direction: column;
  box-shadow: 0 20px 60px rgba(0, 0, 0, 0.22);
}

.rp-modal-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 1rem 1.25rem;
  border-bottom: 1px solid #eaecf6;
  flex-shrink: 0;
}

.rp-modal-title {
  margin: 0;
  font-size: 1rem;
  font-weight: 700;
  color: #1f2937;
}

.rp-modal-body {
  padding: 1.25rem;
  overflow-y: auto;
  flex: 1 1 auto;
}

.rp-modal-footer {
  padding: 1rem 1.25rem;
  border-top: 1px solid #eaecf6;
  display: flex;
  justify-content: flex-end;
  gap: 0.5rem;
  flex-shrink: 0;
}

/* Role label in edit mode */
.role-label {
  font-size: 15px;
  font-weight: 600;
  color: #1f2937;
  background: #f5f7ff;
  border: 1px solid #c7d2fe;
  border-radius: 10px;
  padding: 8px 14px;
  display: inline-block;
}

/* Selected count badge */
.badge-count {
  display: inline-block;
  background: #eef2ff;
  color: #3a5bd9;
  font-size: 11px;
  font-weight: 700;
  border-radius: 50px;
  padding: 1px 8px;
  margin-left: 6px;
}

/* Scrollable permissions list */
.perm-list {
  max-height: 280px;
  overflow-y: auto;
  border: 1px solid #eaecf6;
  border-radius: 12px;
  padding: 6px 4px;
}

.perm-check-item {
  display: flex;
  align-items: center;
  gap: 10px;
  padding: 7px 12px;
  border-radius: 8px;
  cursor: pointer;
  transition: background 0.12s;
  font-size: 13px;
  color: #374151;
  user-select: none;
}

.perm-check-item:hover {
  background: #f5f7ff;
}

.perm-check-item.is-checked {
  background: #eef2ff;
  color: #3a5bd9;
  font-weight: 500;
}

.perm-checkbox {
  width: 16px;
  height: 16px;
  border-radius: 4px;
  border-color: #5b8df6;
  flex-shrink: 0;
  cursor: pointer;
}

.perm-checkbox:checked {
  background-color: #3a5bd9;
  border-color: #3a5bd9;
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
