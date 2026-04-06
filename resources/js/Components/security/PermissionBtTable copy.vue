<script setup>
import { ref, onMounted, onBeforeUnmount, nextTick, watch } from "vue";
import { router, usePage } from "@inertiajs/vue3";
import $ from "jquery";
import PermissionFormModal from "./PermissionFormModal.vue";
import axios from "axios";
import Swal from "sweetalert2";

const tableRef = ref(null);

const showEditModal = ref(false);
const selectedPermission = ref(null);

const editForm = ref({
  id: null,
  name: "",
});
const editErrors = ref({});
const savingEdit = ref(false);

async function openEditModal(id) {
  try {
    const res = await axios.get(route("permissions.show", id));
    selectedPermission.value = res.data;
    showEditModal.value = true;
  } catch (e) {
    Swal.fire("Error", "Failed to load data", "error");
  }
}

const showCreateModal = ref(false);
const createForm = ref({
  name: "",
});
const createErrors = ref({});
const savingCreate = ref(false);

const page = usePage();
watch(
  () => page.props.flash,
  (flash) => {
    if (flash?.success) {
      toastr.success(flash.success);
    }

    if (flash?.error) {
      toastr.error(flash.error);
    }
  },
  { immediate: true, deep: true }
);

function closeEditModal() {
  showEditModal.value = false;
  selectedPermission.value = null;
  editForm.value = {
    id: null,
    name: "",
  };
  editErrors.value = {};
}

function submitEdit() {
  if (!editForm.value.id) return;

  editErrors.value = {};
  savingEdit.value = true;

  router.put(
    route("permissions.update", editForm.value.id),
    {
      name: editForm.value.name,
    },
    {
      preserveScroll: true,
      onSuccess: () => {
        savingEdit.value = false;
        closeEditModal();

        // Swal.fire({
        //   icon: "success",
        //   title: "Updated!",
        //   text: "Permission updated successfully.",
        //   timer: 1500,
        //   showConfirmButton: false,
        // });

        $(tableRef.value).bootstrapTable("refresh");
      },
      onError: (errors) => {
        savingEdit.value = false;
        editErrors.value = errors;
      },
    }
  );
}

function openCreateModal() {
  createErrors.value = {};
  savingCreate.value = false;
  createForm.value = {
    name: "",
  };
  showCreateModal.value = true;
}

function closeCreateModal() {
  showCreateModal.value = false;
  createForm.value = {
    name: "",
  };
  createErrors.value = {};
}

function submitCreate() {
  createErrors.value = {};
  savingCreate.value = true;

  router.post(
    route("permissions.store"),
    {
      name: createForm.value.name,
    },
    {
      preserveScroll: true,
      onSuccess: () => {
        savingCreate.value = false;
        closeCreateModal();
        $(tableRef.value).bootstrapTable("refresh");
      },
      onError: (errors) => {
        savingCreate.value = false;
        createErrors.value = errors;
      },
    }
  );
}

function initTable() {
  if (!tableRef.value) return;

  const $table = $(tableRef.value);

  // Clean old instance first
  try {
    $table.bootstrapTable("destroy");
    $table.off(".permissionActions");
    $table.off(".bs.table");
  } catch (e) {
    // ignore
  }

  $table.bootstrapTable({
    url: route("permissions.data"),
    method: "get",
    pagination: true,
    sidePagination: "server",
    search: true,
    showRefresh: true,
    height: 600,
    showColumns: true,
    sortName: "id", // default sort column
    sortOrder: "asc", // default direction
    pageList: [10, 25, 50, 100],
    iconsPrefix: "fa",
    icons: { refresh: "fa-sync", columns: "fa-list-ul", search: "fa-search" },
    pageSize: 10,
    queryParams: function (params) {
      return {
        limit: params.limit,
        offset: params.offset,
        search: params.search,
        sort: params.sort,
        order: params.order,
      };
    },
    loadingTemplate: function () {
      return `
        <div class="text-center py-5 text-muted">
            <div class="spinner-border spinner-border-sm me-2"></div>
            Fetching data...
        </div>
    `;
    },
    columns: [
      {
        field: "id",
        title: "ID",
        sortable: true,
        align: "center",
        width: "5%",
        visible: false,
      },
      { field: "name", title: "Name" },
      {
        field: "actions",
        title: "Actions",
        align: "center",
        formatter: function (value, row, index) {
          return `
      <button class="btn btn-sm btn-warning me-1 edit-btn" data-id="${row.id}">
        <i class="fa fa-edit"></i>
      </button>

      <button class="btn btn-sm btn-danger delete-btn" data-id="${row.id}" data-name="${row.name}">
        <i class="fa fa-trash"></i>
      </button>
    `;
        },
      },
      { field: "created_at", title: "Created At", visible: false },
      { field: "updated_at", title: "Updated At", visible: false },
    ],
  });

  $table.on("click-row.bs.table", function (_e, row) {
    console.log("clicked row:", row);
  });

  // DELETE
  $table.on("click.permissionActions", ".delete-btn", function (e) {
    e.preventDefault();
    e.stopPropagation();

    const id = $(this).data("id");
    const name = $(this).data("name");

    Swal.fire({
      title: "Are you sure?",
      text: `Delete "${name}"?`,
      icon: "warning",
      showCancelButton: true,
      confirmButtonColor: "#d33",
      cancelButtonColor: "#6c757d",
      confirmButtonText: "Yes, delete it!",
      cancelButtonText: "Cancel",
    }).then((result) => {
      if (result.isConfirmed) {
        router.delete(route("permissions.destroy", id), {
          preserveScroll: true,

          onSuccess: () => {
            Swal.fire({
              icon: "success",
              title: "Deleted!",
              text: "Permission has been deleted.",
              timer: 1500,
              showConfirmButton: false,
            });

            $(tableRef.value).bootstrapTable("refresh");
          },

          onError: () => {
            Swal.fire({
              icon: "error",
              title: "Failed",
              text: "Delete failed. Please try again.",
            });
          },
        });
      }
    });
  });

  $table.on("click.permissionActions", ".edit-btn", function (e) {
    e.preventDefault();
    e.stopPropagation();

    const id = $(this).data("id");
    openEditModal(id);
  });
}

onMounted(async () => {
  await nextTick();
  initTable();
});

// watch(
//   () => props.rows,
//   (rows) => {
//     if (!tableRef.value) return;
//     $(tableRef.value).bootstrapTable("load", rows);
//   },
//   { deep: true }
// );

onBeforeUnmount(() => {
  if (!tableRef.value) return;

  const $table = $(tableRef.value);

  try {
    $table.off(".bs.table");
    $table.bootstrapTable("destroy");
  } catch (e) {
    // ignore
  }
});
</script>

<template>
  <div>
    <div class="d-flex justify-content-end mb-3">
      <button class="btn btn-primary" @click="openCreateModal">
        <i class="fa fa-plus me-1"></i>
        Add Permission
      </button>
    </div>
    <table ref="tableRef" class="table table-bordered table-hover"></table>

    <PermissionFormModal
      :show="showEditModal"
      :permission="selectedPermission"
      @close="showEditModal = false"
      @saved="
        showEditModal = false;
        selectedPermission = null;
        $(tableRef).bootstrapTable('refresh');
      "
    />
  </div>
</template>