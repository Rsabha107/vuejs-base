<script setup>
import { ref, onMounted, onBeforeUnmount, nextTick } from "vue";
import $ from "jquery";
import axios from "axios";
import Swal from "sweetalert2";

// ── Table state ──────────────────────────────────────────────────
const tableRef  = ref(null);
const searchQuery = ref("");
const isLoading = ref(false);
let   searchTimer = null;

// ── Modal state ──────────────────────────────────────────────────
const showModal      = ref(false);
const modalMode      = ref("create");   // "create" | "edit"
const selectedRoleId = ref(null);
const form           = ref({ name: "" });
const formErrors     = ref({});
const isSaving       = ref(false);

// ── Column visibility panel ──────────────────────────────────────
const showColumnPanel = ref(false);
const allColumns = ref([
    { field: "id",      label: "ID",      visible: true },
    { field: "name",    label: "Name",    visible: true },
    { field: "actions", label: "Actions", visible: true },
]);

// ── Helpers ──────────────────────────────────────────────────────
function getVisibleFields() {
    return allColumns.value.filter((c) => c.visible).map((c) => c.field);
}

function actionFormatter(value, row) {
    return `
        <div class="d-flex gap-1">
            <button class="btn-action btn-edit-role"
                    data-id="${row.id}" data-name="${row.name}" title="Edit">
                <i class="fa fa-pencil-alt"></i>
            </button>
            <button class="btn-action btn-delete-role"
                    data-id="${row.id}" data-name="${row.name}" title="Delete">
                <i class="fa fa-trash"></i>
            </button>
        </div>`;
}

function buildColumns() {
    const visible = getVisibleFields();
    const defs = [
        {
            field: "id",
            title: "ID",
            sortable: true,
            align: "center",
            width: "80px",
        },
        {
            field: "name",
            title: "Name",
            sortable: true,
        },
        {
            field: "actions",
            title: "Actions",
            align: "center",
            width: "120px",
            formatter: actionFormatter,
            searchable: false,
            sortable: false,
        },
    ];
    return defs.filter((d) => visible.includes(d.field));
}

// ── Bootstrap Table init ─────────────────────────────────────────
function initTable() {
    if (!tableRef.value) return;

    const $table = $(tableRef.value);

    try { $table.bootstrapTable("destroy"); } catch (_) {}

    $table.bootstrapTable({
        url: "/api/roles",
        method: "get",
        pagination: true,
        sidePagination: "server",
        search: false,          // we render our own search input
        showRefresh: false,     // we render our own refresh button
        showColumns: false,     // we render our own column panel
        filterControl: false,
        sortName: "id",
        sortOrder: "desc",
        pageList: [10, 25, 50, 100],
        pageSize: 10,
        queryParams(params) {
            return {
                limit:  params.limit,
                offset: params.offset,
                search: searchQuery.value,
                sort:   params.sort,
                order:  params.order,
            };
        },
        columns: buildColumns(),
    });

    // Loading indicators
    $table.on("load-success.bs.table load-error.bs.table", () => {
        isLoading.value = false;
    });

    // Action button delegation
    $table.on("click", ".btn-edit-role", function () {
        const id   = parseInt($(this).data("id"));
        const name = String($(this).data("name"));
        openEditModal({ id, name });
    });

    $table.on("click", ".btn-delete-role", function () {
        const id   = parseInt($(this).data("id"));
        const name = String($(this).data("name"));
        confirmDeleteRole({ id, name });
    });
}

function reloadColumns() {
    if (!tableRef.value) return;
    $(tableRef.value).bootstrapTable("refreshOptions", { columns: buildColumns() });
}

// ── Table controls ───────────────────────────────────────────────
function refreshTable() {
    if (!tableRef.value) return;
    isLoading.value = true;
    $(tableRef.value).bootstrapTable("refresh");
}

function onSearchInput() {
    clearTimeout(searchTimer);
    searchTimer = setTimeout(() => refreshTable(), 400);
}

function clearFilters() {
    searchQuery.value = "";
    refreshTable();
}

// ── Column panel ─────────────────────────────────────────────────
function toggleColumnPanel() {
    showColumnPanel.value = !showColumnPanel.value;
}

function onColumnToggle() {
    // Delay one tick so the v-model updates first
    nextTick(() => reloadColumns());
}

// ── Modal helpers ────────────────────────────────────────────────
function openCreateModal() {
    modalMode.value      = "create";
    selectedRoleId.value = null;
    form.value           = { name: "" };
    formErrors.value     = {};
    showModal.value      = true;
}

function openEditModal(role) {
    modalMode.value      = "edit";
    selectedRoleId.value = role.id;
    form.value           = { name: role.name };
    formErrors.value     = {};
    showModal.value      = true;
}

function closeModal() {
    showModal.value  = false;
    formErrors.value = {};
}

// ── CRUD ─────────────────────────────────────────────────────────
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
            await axios.put(`/roles/${selectedRoleId.value}`, { name: form.value.name });
            window.toastr?.success("Role updated successfully");
        }
        closeModal();
        refreshTable();
    } catch (err) {
        const errors = err.response?.data?.errors;
        if (errors) {
            formErrors.value = errors;
        } else {
            window.toastr?.error("An error occurred. Please try again.");
        }
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

// ── Lifecycle ────────────────────────────────────────────────────
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
    <!-- Outer card -->
    <div class="bt-card">
        <div class="bt-table-shell">

            <!-- ── Toolbar ───────────────────────────────────────── -->
            <div class="bt-toolbar">
                <!-- Left: Clear -->
                <div>
                    <button class="btn btn-outline-secondary btn-sm" @click="clearFilters">
                        <i class="pi pi-filter-slash me-1"></i> Clear
                    </button>
                </div>

                <!-- Right: Search · Add · Refresh · Columns -->
                <div class="d-flex align-items-center gap-2 flex-wrap">
                    <div class="search-box">
                        <input
                            v-model="searchQuery"
                            type="text"
                            class="form-control form-control-sm"
                            placeholder="Keyword Search"
                            @input="onSearchInput"
                        />
                    </div>

                    <button class="btn btn-secondary btn-sm" @click="openCreateModal">
                        <i class="fa fa-plus me-1"></i> Add Role
                    </button>

                    <button
                        class="btn btn-outline-secondary btn-sm"
                        :disabled="isLoading"
                        title="Refresh"
                        @click="refreshTable"
                    >
                        <i :class="isLoading ? 'fa fa-spin fa-sync' : 'fa fa-sync'"></i>
                    </button>

                    <!-- Column panel -->
                    <div class="position-relative">
                        <button
                            class="btn btn-outline-secondary btn-sm"
                            title="Toggle Columns"
                            @click="toggleColumnPanel"
                        >
                            <i class="fa fa-th"></i>
                        </button>

                        <div v-if="showColumnPanel" class="column-panel">
                            <div
                                v-for="col in allColumns"
                                :key="col.field"
                                class="d-flex align-items-center gap-2 mb-2"
                            >
                                <input
                                    type="checkbox"
                                    v-model="col.visible"
                                    :id="`col-${col.field}`"
                                    @change="onColumnToggle"
                                />
                                <label :for="`col-${col.field}`" class="mb-0 small">
                                    {{ col.label }}
                                </label>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ── Bootstrap Table ───────────────────────────────── -->
            <table ref="tableRef"></table>

        </div>
    </div>

    <!-- ── Role Modal ────────────────────────────────────────────── -->
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
                        {{ Array.isArray(formErrors.name) ? formErrors.name[0] : formErrors.name }}
                    </div>
                </div>

                <div class="role-modal-footer">
                    <button class="btn btn-light" @click="closeModal">Cancel</button>
                    <button class="btn btn-primary" :disabled="isSaving" @click="saveRole">
                        <span v-if="isSaving" class="spinner-border spinner-border-sm me-1"></span>
                        Save
                    </button>
                </div>
            </div>
        </div>
    </Teleport>
</template>

<style scoped>
/* ── Card wrapper ─────────────────────────────────────────────── */
.bt-card {
    background: #fff;
    border-radius: 16px;
    overflow: hidden;
    border: 1px solid #e5e7eb;
    box-shadow: 0 0.125rem 0.5rem rgba(15, 23, 42, 0.06);
}

.bt-table-shell {
    border-radius: 16px;
    overflow: hidden;
}

/* ── Toolbar ──────────────────────────────────────────────────── */
.bt-toolbar {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 1rem;
    flex-wrap: wrap;
    padding: 1rem;
    border-bottom: 1px solid #eef2f7;
    background: #fff;
}

.search-box {
    min-width: 260px;
}

/* ── Column panel ─────────────────────────────────────────────── */
.column-panel {
    position: absolute;
    right: 0;
    top: calc(100% + 4px);
    background: #fff;
    border: 1px solid #e5e7eb;
    border-radius: 10px;
    padding: 10px 14px;
    box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
    z-index: 1000;
    min-width: 140px;
}

/* ── Bootstrap Table overrides ────────────────────────────────── */
/* Hide the built-in toolbar Bootstrap Table generates */
:deep(.fixed-table-toolbar) {
    display: none !important;
}

:deep(thead th) {
    background: #f8fafc !important;
    color: #344054;
    font-size: 14px;
    font-weight: 700;
    border-bottom: 1px solid #e5e7eb;
}

:deep(tbody td) {
    vertical-align: middle;
    font-size: 14px;
}

:deep(.fixed-table-pagination) {
    border-top: 1px solid #eef2f7;
    padding: 0.5rem 1rem;
}

:deep(table) {
    margin-bottom: 0 !important;
}

/* ── Action buttons ───────────────────────────────────────────── */
:deep(.btn-action) {
    width: 34px;
    height: 34px;
    border-radius: 10px;
    border: 1px solid transparent;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    background: #fff;
    transition: all 0.2s ease;
    padding: 0;
    cursor: pointer;
}

:deep(.btn-edit-role) {
    background: #fff7e6;
    border-color: #fde7b0;
    color: #d97706;
}
:deep(.btn-edit-role:hover) { background: #fef3c7; }

:deep(.btn-delete-role) {
    background: #fff1f2;
    border-color: #fecdd3;
    color: #dc2626;
}
:deep(.btn-delete-role:hover) { background: #ffe4e6; }

/* ── Modal ────────────────────────────────────────────────────── */
.role-modal-overlay {
    position: fixed;
    inset: 0;
    background: rgba(0, 0, 0, 0.4);
    z-index: 1050;
    display: flex;
    align-items: center;
    justify-content: center;
}

.role-modal-box {
    background: #fff;
    border-radius: 16px;
    width: 440px;
    max-width: 95vw;
    box-shadow: 0 20px 60px rgba(0, 0, 0, 0.2);
}

.role-modal-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 1rem 1.25rem;
    border-bottom: 1px solid #eef2f7;
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
    border-top: 1px solid #eef2f7;
    display: flex;
    justify-content: flex-end;
    gap: 0.5rem;
}

/* ── Responsive ───────────────────────────────────────────────── */
@media (max-width: 768px) {
    .bt-toolbar {
        flex-direction: column;
        align-items: stretch;
    }
    .search-box {
        width: 100%;
        min-width: unset;
    }
}
</style>
