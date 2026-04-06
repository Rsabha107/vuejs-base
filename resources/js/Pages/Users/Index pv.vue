<script setup>
import { ref, watch, computed, onMounted } from "vue";
import { Head, router, useForm } from "@inertiajs/vue3";

import DataTable from "primevue/datatable";
import Column from "primevue/column";
import InputText from "primevue/inputtext";
import Button from "primevue/button";
import Dialog from "primevue/dialog";
import Dropdown from "primevue/dropdown";
import MultiSelect from "primevue/multiselect";

import HeaderNavbar from "@/Components/HeaderNavbar.vue";
import VerticalMenu from "@/Components/VerticalMenu.vue";
import UsersBtTable from "@/Components/UsersBtTable.vue";

const props = defineProps({
  users: Object,
  filters: Object,
});

const search = ref(props.filters.search || "");
const perPage = ref(props.filters.per_page || 10);

const showCreateModal = ref(false);
const showEditModal = ref(false);
const editingUserId = ref(null);

let searchDebounce = null;

watch(search, (value) => {
  clearTimeout(searchDebounce);

  searchDebounce = setTimeout(() => {
    loadData({
      search: value,
      page: 1,
      per_page: perPage.value,
      sort_field: props.filters.sort_field || "id",
      sort_order: props.filters.sort_order || "desc",
    });
  }, 400);
});

watch(perPage, (value) => {
  loadData({
    search: search.value,
    page: 1,
    per_page: value,
    sort_field: props.filters.sort_field || "id",
    sort_order: props.filters.sort_order || "desc",
  });
});

const loading = ref(false);

function refreshTable() {
  loading.value = true;

  router.get(
    route("users.index"),
    {
      search: search.value,
      page: props.users.current_page,
      per_page: perPage.value,
      sort_field: props.filters.sort_field,
      sort_order: props.filters.sort_order,
    },
    {
      preserveState: true,
      preserveScroll: true,
      replace: true,
      onFinish: () => {
        loading.value = false;
      },
    }
  );
}

const perPageOptions = [10, 25, 50, 100];

const allColumns = [
  { field: "id", header: "ID", sortable: true },
  { field: "name", header: "Name", sortable: true },
  { field: "email", header: "Email", sortable: true },
  { field: "created_at", header: "Created", sortable: true },
];

const STORAGE_KEY = "users_table_selected_columns";

const selectedColumns = ref([
  { field: "id", header: "ID", sortable: true },
  { field: "name", header: "Name", sortable: true },
  { field: "email", header: "Email", sortable: true },
]);

const visibleColumns = computed(() => {
  return allColumns.filter((col) =>
    selectedColumns.value.some((selected) => selected.field === col.field)
  );
});

function onColumnsChange(value) {
  if (!value || !value.length) return;
  selectedColumns.value = value;
}

onMounted(() => {
  const saved = localStorage.getItem(STORAGE_KEY);
  if (!saved) return;

  try {
    const fields = JSON.parse(saved);
    const matched = allColumns.filter((col) => fields.includes(col.field));
    if (matched.length) {
      selectedColumns.value = matched;
    }
  } catch (e) {
    console.warn("Failed to load saved columns", e);
  }
});

watch(
  selectedColumns,
  (value) => {
    localStorage.setItem(
      STORAGE_KEY,
      JSON.stringify(value.map((col) => col.field))
    );
  },
  { deep: true }
);

const createForm = useForm({
  name: "",
  email: "",
  password: "",
});

const editForm = useForm({
  name: "",
  email: "",
});

function loadData(params = {}) {
  router.get(route("users.index"), params, {
    preserveState: true,
    preserveScroll: true,
    replace: true,
  });
}

function onPage(event) {
  const page = event.page + 1;

  loadData({
    search: search.value,
    page,
    per_page: event.rows,
    sort_field: props.filters.sort_field || "id",
    sort_order: props.filters.sort_order || "desc",
  });
}

function onSort(event) {
  loadData({
    search: search.value,
    page: 1,
    per_page: perPage.value,
    sort_field: event.sortField,
    sort_order: event.sortOrder === 1 ? "asc" : "desc",
  });
}

function openCreate() {
  createForm.reset();
  createForm.clearErrors();
  showCreateModal.value = true;
}

function submitCreate() {
  createForm.post(route("users.store"), {
    preserveScroll: true,
    onSuccess: () => {
      showCreateModal.value = false;
      createForm.reset();
    },
  });
}

function openEdit(user) {
  editingUserId.value = user.id;
  editForm.name = user.name;
  editForm.email = user.email;
  editForm.clearErrors();
  showEditModal.value = true;
}

function submitEdit() {
  editForm.put(route("users.update", editingUserId.value), {
    preserveScroll: true,
    onSuccess: () => {
      showEditModal.value = false;
    },
  });
}

function deleteUser(user) {
  if (!window.confirm(`Delete ${user.name}?`)) return;

  router.delete(route("users.destroy", user.id), {
    preserveScroll: true,
  });
}
</script>

<template>
  <Head title="Users -- vue" />

  <div id="layout-wrapper">
    <header id="page-topbar">
      <!-- {{-- App Header --}} -->
      <HeaderNavbar />
    </header>
    <!-- ========== Left Sidebar Start ========== -->
    <div class="vertical-menu">
      <!-- {{-- App Vertical Menu --}} -->
      <VerticalMenu />
    </div>
    <div class="main-content">
      <div class="page-content">
        <div class="container py-4">
          <div class="card shadow-sm border-0">
            <div
              class="card-header bg-white d-flex flex-wrap justify-content-between align-items-center gap-2"
            >
              <h4 class="mb-0">Users</h4>

              <div class="d-flex flex-wrap gap-2 align-items-center">
                <InputText
                  v-model="search"
                  placeholder="Search name or email"
                  class="w-auto"
                />
                <MultiSelect
                  :modelValue="selectedColumns"
                  @update:modelValue="onColumnsChange"
                  :options="allColumns"
                  optionLabel="header"
                  display="chip"
                  placeholder="Columns"
                  class="w-auto"
                  :maxSelectedLabels="2"
                />
                <Button
                  icon="pi pi-refresh"
                  class="p-button-sm p-button-outlined"
                  @click="refreshTable"
                  :loading="loading"
                />
                <Dropdown
                  v-model="perPage"
                  :options="perPageOptions"
                  placeholder="Rows"
                  class="w-auto"
                />

                <Button
                  label="Add User"
                  icon="pi pi-plus"
                  @click="openCreate"
                />
              </div>
            </div>

            <div class="card-body">
              <DataTable
                :value="users.data"
                paginator
                lazy
                :loading="false"
                :rows="users.per_page"
                :totalRecords="users.total"
                :first="(users.current_page - 1) * users.per_page"
                responsiveLayout="scroll"
                tableStyle="min-width: 60rem"
                sortMode="single"
                :sortField="filters.sort_field"
                :sortOrder="filters.sort_order === 'asc' ? 1 : -1"
                @page="onPage"
                @sort="onSort"
              >
                <Column
                  v-for="col in visibleColumns"
                  :key="col.field"
                  :field="col.field"
                  :header="col.header"
                  :sortable="col.sortable"
                >
                  <template v-if="col.field === 'created_at'" #body="{ data }">
                    {{ new Date(data.created_at).toLocaleDateString() }}
                  </template>
                </Column>

                <Column header="Actions">
                  <template #body="{ data }">
                    <div class="d-flex gap-2">
                      <button
                        class="btn btn-sm btn-outline-primary"
                        @click="openEdit(data)"
                      >
                        Edit
                      </button>
                      <button
                        class="btn btn-sm btn-outline-danger"
                        @click="deleteUser(data)"
                      >
                        Delete
                      </button>
                    </div>
                  </template>
                </Column>
              </DataTable>
            </div>
          </div>

          <Dialog
            v-model:visible="showCreateModal"
            modal
            header="Create User"
            :style="{ width: '30rem' }"
          >
            <form @submit.prevent="submitCreate">
              <div class="mb-3">
                <label class="form-label">Name</label>
                <input
                  v-model="createForm.name"
                  type="text"
                  class="form-control"
                />
                <div
                  v-if="createForm.errors.name"
                  class="text-danger small mt-1"
                >
                  {{ createForm.errors.name }}
                </div>
              </div>

              <div class="mb-3">
                <label class="form-label">Email</label>
                <input
                  v-model="createForm.email"
                  type="email"
                  class="form-control"
                />
                <div
                  v-if="createForm.errors.email"
                  class="text-danger small mt-1"
                >
                  {{ createForm.errors.email }}
                </div>
              </div>

              <div class="mb-3">
                <label class="form-label">Password</label>
                <input
                  v-model="createForm.password"
                  type="password"
                  class="form-control"
                />
                <div
                  v-if="createForm.errors.password"
                  class="text-danger small mt-1"
                >
                  {{ createForm.errors.password }}
                </div>
              </div>

              <div class="d-flex justify-content-end gap-2">
                <button
                  type="button"
                  class="btn btn-light"
                  @click="showCreateModal = false"
                >
                  Cancel
                </button>
                <button
                  type="submit"
                  class="btn btn-primary"
                  :disabled="createForm.processing"
                >
                  {{ createForm.processing ? "Saving..." : "Save" }}
                </button>
              </div>
            </form>
          </Dialog>

          <Dialog
            v-model:visible="showEditModal"
            modal
            header="Edit User"
            :style="{ width: '30rem' }"
          >
            <form @submit.prevent="submitEdit">
              <div class="mb-3">
                <label class="form-label">Name</label>
                <input
                  v-model="editForm.name"
                  type="text"
                  class="form-control"
                />
                <div v-if="editForm.errors.name" class="text-danger small mt-1">
                  {{ editForm.errors.name }}
                </div>
              </div>

              <div class="mb-3">
                <label class="form-label">Email</label>
                <input
                  v-model="editForm.email"
                  type="email"
                  class="form-control"
                />
                <div
                  v-if="editForm.errors.email"
                  class="text-danger small mt-1"
                >
                  {{ editForm.errors.email }}
                </div>
              </div>

              <div class="d-flex justify-content-end gap-2">
                <button
                  type="button"
                  class="btn btn-light"
                  @click="showEditModal = false"
                >
                  Cancel
                </button>
                <button
                  type="submit"
                  class="btn btn-primary"
                  :disabled="editForm.processing"
                >
                  {{ editForm.processing ? "Updating..." : "Update" }}
                </button>
              </div>
            </form>
          </Dialog>
        </div>
      </div>
      <!-- end modal -->
      <footer class="footer">
        <div class="container-fluid">
          <div class="row">
            <div class="col-sm-6">
              <!-- <script>
                            document.write(new Date().getFullYear())
                            </script> -->
              © StarCode Kh.
            </div>
            <div class="col-sm-6">
              <div class="text-sm-end d-none d-sm-block">
                Design &amp; Custom by StarCode Kh
              </div>
            </div>
          </div>
        </div>
      </footer>
    </div>
  </div>
</template>