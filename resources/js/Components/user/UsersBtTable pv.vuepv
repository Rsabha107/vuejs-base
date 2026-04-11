<script setup>
import { ref, watch } from "vue";
import { router } from "@inertiajs/vue3";

import DataTable from "primevue/datatable";
import Column from "primevue/column";
import InputText from "primevue/inputtext";
import Button from "primevue/button";
import Breadcrumb from "primevue/breadcrumb";
import Tag from "primevue/tag";
import MultiSelect from "primevue/multiselect";
import axios from "axios";
import UserFormModal from "@/Components/user/UserFormModal.vue";
import Swal from "sweetalert2";
import { FilterMatchMode, FilterOperator } from "@primevue/core/api";

const props = defineProps({
  rows: {
    type: Array,
    default: () => [],
  },
  totalRecords: {
    type: Number,
    default: 0,
  },
  lazyParams: {
    type: Object,
    default: () => ({
      page: 1,
      rows: 10,
      sortField: "id",
      sortOrder: -1,
      filters: {},
    }),
  },
});

// start of ref and reactive variables

const globalSearch = ref(props.lazyParams?.filters?.global?.value ?? "");
const loading = ref(false);
const suppressWatch = ref(false);

// modal state for create/edit user
const showUserModal = ref(false);
const modalMode = ref("create");
const selectedUser = ref(null);

const home = ref({
  icon: "pi pi-home", 
  url: route("home"),
});

const items = ref([
  { label: "User Management", url: "/users", icon: "pi pi-users" },
]);

const rowsPerPage = ref(props.lazyParams.rows || 10);
const sortField = ref(props.lazyParams.sortField || "id");
const sortOrder = ref(props.lazyParams.sortOrder ?? -1);

const showColumnPanel = ref(false);
const totalRecords = ref(props.totalRecords ?? 0);

const filters = ref(
  props.lazyParams.filters && Object.keys(props.lazyParams.filters).length
    ? props.lazyParams.filters
    : defaultFilters()
);

const first = ref(
  ((props.lazyParams.page || 1) - 1) * (props.lazyParams.rows || 10)
);

const allColumns = ref([
  { field: "id", header: "ID" },
  { field: "name", header: "Name" },
  { field: "email", header: "Email" },
  { field: "status_id", header: "Status" },
  { field: "actions", header: "Actions" },
]);

const selectedColumns = ref([...allColumns.value]);

const statusOptions = ref([
  { label: "Active", value: 1 },
  { label: "Inactive", value: 2 },
]);

// end of ref and reactive variables

function exportExcel() {
  console.log("Exporting to Excel with current table state...");
  const params = new URLSearchParams();

  params.append("page", 1);
  params.append("per_page", rowsPerPage.value || 10);
  params.append("sort_field", sortField.value || "id");
  params.append("sort_order", (sortOrder.value ?? -1) === 1 ? "asc" : "desc");
  params.append("filters", JSON.stringify(filters.value));
  params.append(
    "columns",
    JSON.stringify(
      selectedColumns.value
        .map((c) => c.field)
        .filter((field) => field !== "actions")
    )
  );

  window.open(`${route("users.export")}?${params.toString()}`, "_blank");
}

function toggleColumnPanel() {
  showColumnPanel.value = !showColumnPanel.value;
}

// table state

watch(
  () => props.totalRecords,
  (val) => {
    totalRecords.value = val ?? 0;
  },
  { immediate: true }
);

function openCreateModal() {
  modalMode.value = "create";
  selectedUser.value = null;
  showUserModal.value = true;
}

function editUser(row) {
  modalMode.value = "edit";
  selectedUser.value = { ...row };
  showUserModal.value = true;
}

function closeUserModal() {
  showUserModal.value = false;
  selectedUser.value = null;
}

function handleSaved() {
  showUserModal.value = false;
  selectedUser.value = null;

  refreshTable();
}

function deleteUser(row) {
  Swal.fire({
    title: "Delete user?",
    text: `Are you sure you want to delete ${row.name}?`,
    icon: "warning",
    showCancelButton: true,
    confirmButtonText: "Yes, delete",
  }).then((result) => {
    if (!result.isConfirmed) return;

    router.delete(route("users.destroy", row.id), {
      preserveScroll: true,
      onSuccess: () => {
        if (window.toastr) window.toastr.success("User deleted successfully");
        refreshTable();
      },
    });
  });
}

function defaultFilters() {
  return {
    global: { value: null, matchMode: FilterMatchMode.CONTAINS },

    id: {
      operator: FilterOperator.AND,
      constraints: [{ value: null, matchMode: FilterMatchMode.EQUALS }],
    },
    name: {
      operator: FilterOperator.AND,
      constraints: [{ value: null, matchMode: FilterMatchMode.CONTAINS }],
    },
    email: {
      operator: FilterOperator.AND,
      constraints: [{ value: null, matchMode: FilterMatchMode.CONTAINS }],
    },
    status_id: {
      operator: FilterOperator.OR,
      constraints: [{ value: null, matchMode: FilterMatchMode.EQUALS }],
    },
  };
}

watch(
  () => props.lazyParams,
  (val) => {
    suppressWatch.value = true;

    first.value = ((val.page || 1) - 1) * (val.rows || 10);
    rowsPerPage.value = val.rows || 10;
    sortField.value = val.sortField || "id";
    sortOrder.value = val.sortOrder ?? -1;
    filters.value =
      val.filters && Object.keys(val.filters).length
        ? val.filters
        : defaultFilters();
    globalSearch.value = val.filters?.global?.value ?? "";

    setTimeout(() => {
      suppressWatch.value = false;
    }, 0);
  },
  { deep: true }
);

const tableRows = ref(normalizeRows(props.rows));

console.log("Initial table rows", tableRows);


function normalizeRows(rows) {
  // console.log("Normalizing rows", rows);
  return (rows || []).map((row) => ({
    id: row.id,
    name: row.name ?? "-",
    email: row.email ?? "-",
    status_id: row.status?.id ?? null,
    is_active: row.status?.is_active ?? false,
    status_name: row.status?.name ?? "",
    status_color: row.status?.color ?? "",
    ...row,
  }));
}

watch(
  () => props.rows,
  (rows) => {
    tableRows.value = normalizeRows(rows);
  },
  { deep: true, immediate: true }
);


async function reloadTable(extra = {}) {
  loading.value = true;

  const payload = {
    page:
      Math.floor(
        (extra.first ?? first.value) / (extra.rows ?? rowsPerPage.value)
      ) + 1,
    per_page: extra.rows ?? rowsPerPage.value,
    sort_field: extra.sortField ?? sortField.value,
    sort_order: (extra.sortOrder ?? sortOrder.value) === 1 ? "asc" : "desc",
    filters: JSON.stringify(extra.filters ?? filters.value),
  };

  try {
    const res = await axios.post(route("users.table"), payload);

    tableRows.value = res.data.data;
    totalRecords.value = res.data.total;

    // console.log("Reloaded table rows", tableRows.value);

    // sync lazy params
    first.value = (res.data.lazyParams.page - 1) * res.data.lazyParams.rows;
    rowsPerPage.value = res.data.lazyParams.rows;
    sortField.value = res.data.lazyParams.sortField;
    sortOrder.value = res.data.lazyParams.sortOrder;
    // filters.value = res.data.lazyParams.filters;
  } finally {
    loading.value = false;
  }
}

function onPage(event) {
  first.value = event.first;
  rowsPerPage.value = event.rows;

  reloadTable({
    first: event.first,
    rows: event.rows,
    sortField: sortField.value,
    sortOrder: sortOrder.value,
    filters: filters.value,
  });
}

function onSort(event) {
  if (!event.sortField) return;

  sortField.value = event.sortField;
  sortOrder.value = event.sortOrder ?? 1;
  first.value = 0;

  reloadTable({
    first: 0,
    rows: rowsPerPage.value,
    sortField: sortField.value,
    sortOrder: sortOrder.value,
    filters: filters.value,
  });
}

let filterTimer = null;

function queueFilterReload(nextFilters) {
  clearTimeout(filterTimer);

  filterTimer = setTimeout(() => {
    reloadTable({
      first: 0,
      rows: rowsPerPage.value,
      sortField: sortField.value,
      sortOrder: sortOrder.value,
      filters: nextFilters,
    });
  }, 400);
}

let globalSearchTimer = null;

function onGlobalSearchInput() {
  if (suppressWatch.value) return;

  filters.value.global.value = globalSearch.value;
  first.value = 0;

  clearTimeout(globalSearchTimer);
  globalSearchTimer = setTimeout(() => {
    reloadTable({
      first: 0,
      rows: rowsPerPage.value,
      sortField: sortField.value,
      sortOrder: sortOrder.value,
      filters: filters.value,
    });
  }, 400);
}

function onFilter(event) {
  filters.value = event.filters;
  first.value = 0;

  if (suppressWatch.value) return;

  queueFilterReload(event.filters);
}

watch(
  () => filters.value.status_id?.constraints?.[0]?.value,
  () => {
    if (suppressWatch.value) return;

    first.value = 0;
    queueFilterReload(filters.value);
  },
  { deep: true }
);

function clearFilters() {
  filters.value = defaultFilters();
  first.value = 0;

  reloadTable({
    first: 0,
    rows: rowsPerPage.value,
    sortField: sortField.value,
    sortOrder: sortOrder.value,
    filters: filters.value,
  });
}

function refreshTable() {
  reloadTable({
    first: first.value,
    rows: rowsPerPage.value,
    sortField: sortField.value,
    sortOrder: sortOrder.value,
    filters: filters.value,
    showToast: true,
  });
}

function getSeverity(status) {
  switch (status) {
    case "qualified":
      return "success";
    case "negotiation":
      return "warning";
    case "unqualified":
      return "danger";
    case "new":
      return "info";
    case "renewal":
      return "secondary";
    default:
      return "contrast";
  }
}
</script>

<template>
  <div class="d-flex align-items-center gap-2 flex-wrap mb-4">
    <Breadcrumb :home="home" :model="items" />
  </div>
  <div class="prime-card">
    <!-- <div class="prime-card-header">

    </div> -->

    <div class="prime-table-shell">
      <DataTable
        :value="tableRows"
        v-model:filters="filters"
        :lazy="true"
        :loading="loading"
        filterDisplay="menu"
        paginator
        :first="first"
        :rows="rowsPerPage"
        :totalRecords="totalRecords"
        :rowsPerPageOptions="[10, 25, 50, 100]"
        paginatorTemplate="FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink RowsPerPageDropdown CurrentPageReport"
        currentPageReportTemplate="Showing {first} to {last} of {totalRecords} users"
        :sortField="sortField"
        :sortOrder="sortOrder"
        dataKey="id"
        responsiveLayout="scroll"
        rowHover
        showGridlines
        removableSort
        scrollable
        scrollHeight="500px"
        class="users-prime-table"
        @page="onPage"
        @sort="onSort"
        @filter="onFilter"
      >
        <template #header>
          <div class="table-toolbar">
            <div>
              <Button
                type="button"
                icon="pi pi-filter-slash"
                label="Clear"
                outlined
                @click="clearFilters"
              />
            </div>

            <div class="d-flex align-items-center gap-2 flex-wrap">
              <span class="p-input-icon-left search-box">
                <InputText
                  v-model="globalSearch"
                  placeholder="Keyword Search"
                  @input="onGlobalSearchInput"
                />
              </span>
              <Button
                icon="pi pi-plus"
                label="Add User"
                severity="secondary"
                @click="openCreateModal"
              />

              <Button
                label=""
                severity="secondary"
                v-tooltip.top="'Export Excel'"
                icon="pi pi-download"
                @click="exportExcel"
              />

              <Button
                :icon="loading ? 'pi pi-spin pi-spinner' : 'pi pi-sync'"
                label=""
                severity="secondary"
                outlined
                :disabled="loading"
                v-tooltip.top="'Refresh'"
                @click="refreshTable"
              />

              <div class="position-relative">
                <Button
                  icon="pi pi-table"
                  label=""
                  v-tooltip.top="'Columns'"
                  severity="secondary"
                  outlined
                  @click="showColumnPanel = !showColumnPanel"
                />

                <div v-if="showColumnPanel" class="column-panel">
                  <div
                    v-for="col in allColumns"
                    :key="col.field"
                    class="d-flex align-items-center gap-2 mb-2"
                  >
                    <input
                      type="checkbox"
                      :value="col"
                      v-model="selectedColumns"
                    />
                    <span>{{ col.header }}</span>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </template>

        <Column
          v-if="selectedColumns.some((c) => c.field === 'id')"
          field="id"
          header="ID"
          sortable
          style="min-width: 90px"
        >
          <template #body="{ data }">
            <span class="fw-semibold text-muted">{{ data.id }}</span>
          </template>
          <template #filter="{ filterModel }">
            <InputText v-model="filterModel.value" placeholder="Search ID" />
          </template>
        </Column>

        <Column
          v-if="selectedColumns.some((c) => c.field === 'name')"
          field="name"
          header="Name"
          sortable
          style="min-width: 200px"
        >
          <template #body="{ data }">
            <span class="fw-semibold text-dark">{{ data.name }}</span>
          </template>
          <template #filter="{ filterModel }">
            <InputText v-model="filterModel.value" placeholder="Search name" />
          </template>
        </Column>

        <Column
          v-if="selectedColumns.some((c) => c.field === 'email')"
          field="email"
          header="Email"
          sortable
          style="min-width: 240px"
        >
          <template #body="{ data }">
            <span class="text-muted">{{ data.email }}</span>
          </template>
          <template #filter="{ filterModel }">
            <InputText v-model="filterModel.value" placeholder="Search email" />
          </template>
        </Column>

        <Column
          v-if="selectedColumns.some((c) => c.field === 'status_id')"
          field="status_id"
          header="Status"
          sortable
          style="min-width: 170px"
        >
          <template #body="{ data }">
            <Tag
              :value="data.status_name"
              :severity="data.status_color"
              rounded
            />
          </template>
          <template #filter="{ filterModel }">
            <MultiSelect
              v-model="filterModel.value"
              :options="statusOptions"
              optionLabel="label"
              optionValue="value"
              placeholder="Any"
              display="chip"
            />
          </template>
        </Column>

        <Column
          v-if="selectedColumns.some((c) => c.field === 'actions')"
          header="Actions"
          style="min-width: 150px"
        >
          <template #body="{ data }">
            <div class="action-group">
              <button class="btn-action btn-edit" @click="editUser(data)">
                <i class="fa fa-pencil-alt"></i>
              </button>
              <button class="btn-action btn-delete" @click="deleteUser(data)">
                <i class="fa fa-trash"></i>
              </button>
            </div>
          </template>
        </Column>

        <template #empty>
          <div class="text-center py-4 text-muted">No users found.</div>
        </template>
      </DataTable>
    </div>
  </div>

  <UserFormModal
    :show="showUserModal"
    :mode="modalMode"
    :user="selectedUser"
    @close="closeUserModal"
    @saved="handleSaved"
  />
</template>

<style scoped>
.prime-card {
  background: #fff;
  border-radius: 18px;
  padding: 1.25rem;
  box-shadow: 0 0.125rem 0.5rem rgba(15, 23, 42, 0.06);
  border: 1px solid #eef2f7;
}

.prime-card-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 1rem;
  margin-bottom: 1rem;
  flex-wrap: wrap;
}

.prime-title {
  font-size: 1.2rem;
  font-weight: 700;
  color: #1f2937;
}

.prime-subtitle {
  color: #6b7280;
  font-size: 0.9rem;
}

.prime-table-shell {
  border: 1px solid #e5e7eb;
  border-radius: 16px;
  overflow: hidden;
  background: #fff;
}

.table-toolbar {
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 1rem;
  flex-wrap: wrap;
}

.search-box {
  min-width: 280px;
}

.search-box :deep(input) {
  width: 100%;
}

.column-picker {
  min-width: 220px;
}

.action-group {
  display: inline-flex;
  align-items: center;
  gap: 8px;
}

.btn-action {
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
}

.btn-edit {
  background: #fff7e6;
  border-color: #fde7b0;
  color: #d97706;
}

.btn-edit:hover {
  background: #fef3c7;
}

.btn-delete {
  background: #fff1f2;
  border-color: #fecdd3;
  color: #dc2626;
}

.btn-delete:hover {
  background: #ffe4e6;
}

.users-prime-table :deep(.p-datatable-header) {
  background: #fff;
  border-bottom: 1px solid #eef2f7;
  padding: 1rem;
}

.users-prime-table :deep(.p-datatable-thead > tr > th) {
  background: #f8fafc;
  color: #344054;
  font-size: 14px;
  font-weight: 700;
}

.users-prime-table :deep(.p-datatable-tbody > tr > td) {
  vertical-align: middle;
}

.users-prime-table :deep(.p-paginator) {
  border-top: 1px solid #eef2f7;
}

:deep(.p-breadcrumb) {
    background: transparent;
}

@media (max-width: 768px) {
  .table-toolbar {
    flex-direction: column;
    align-items: stretch;
  }

  .search-box,
  .column-picker {
    width: 100%;
    min-width: unset;
  }
}

/* Change active page color */
/* .users-prime-table :deep(.p-paginator-page.p-paginator-page-selected) {
  background: #0d6efd !important;
  color: #fff !important;
  border-color: #0d6efd !important;
}

.users-prime-table :deep(.p-paginator-page.p-paginator-page-selected:hover) {
  background: #0b5ed7 !important;
  color: #fff !important;
  border-color: #0b5ed7 !important;
}

.p-button {
  background: #0d6efd !important;
  color: #fff !important;
  border-color: #0d6efd;
}

.p-button:not(:disabled):hover {
  background: #c3d4ec !important;
  border-color: #0b5ed7 !important;
  color: #0b5ed7 !important;
}

.p-button-outlined {
  background: transparent;
  color: #0b5ed7 !important;
  border-color: #0b5ed7 !important;
}

.p-button-outlined:not(:disabled):hover {
  background: #c3d4ec !important;
  border-color: #0b5ed7 !important;
  color: #0b5ed7 !important;
} */

.column-dropdown :deep(.p-multiselect-label) {
  display: flex;
  align-items: center;
  gap: 6px;
}

.column-dropdown :deep(.p-multiselect-label)::before {
  content: "\f0db"; /* fa-columns */
  font-family: "Font Awesome 5 Free";
  font-weight: 900;
}

.column-panel {
  position: absolute;
  right: 0;
  background: #fff;
  border: 1px solid #e5e7eb;
  border-radius: 10px;
  padding: 10px;
  box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
  z-index: 1000;
}
</style>