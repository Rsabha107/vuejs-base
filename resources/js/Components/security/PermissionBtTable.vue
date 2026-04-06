<script setup>
import { ref, onMounted, onBeforeUnmount, nextTick } from "vue";
import { router } from "@inertiajs/vue3";
import axios from "axios";
import Swal from "sweetalert2";
import $ from "jquery";
import PermissionModal from "./PermissionFormModal.vue";


const tableRef = ref(null);

const showPermissionModal = ref(false);
const modalMode = ref("create");
const selectedPermission = ref(null);

function openCreateModal() {
  modalMode.value = "create";
  selectedPermission.value = null;
  showPermissionModal.value = true;
}

async function openEditModal(id) {
  try {
    const response = await axios.get(route("permissions.show", id));

    modalMode.value = "edit";
    selectedPermission.value = response.data;
    showPermissionModal.value = true;
  } catch (error) {
    console.error(error);
    Swal.fire({
      icon: "error",
      title: "Failed",
      text: "Could not load permission details.",
    });
  }
}

function closePermissionModal() {
  showPermissionModal.value = false;
  selectedPermission.value = null;
}

function handleSaved() {
  closePermissionModal();
  $(tableRef.value).bootstrapTable("refresh");
}

function initTable() {
  if (!tableRef.value) return;

  const $table = $(tableRef.value);

  try {
    $table.bootstrapTable("destroy");
    $table.off(".permissionActions");
    $table.off(".bs.table");
  } catch (e) {
    //
  }

  $table.bootstrapTable({
    url: route("permissions.data"),
    method: "get",
    pagination: true,
    sidePagination: "server",
    search: true,
    showRefresh: true,
    showColumns: true,
    height: 600,
    sortName: "id",
    sortOrder: "asc",
    pageList: [10, 25, 50, 100],
    pageSize: 10,
    iconsPrefix: "fa",
    icons: {
      refresh: "fa-sync",
      columns: "fa-list-ul",
      search: "fa-search",
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
          <div class="fw-semibold">Loading permissions...</div>
          <div class="text-muted small mt-1">Please wait a moment</div>
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
      {
        field: "name",
        title: "Name",
        sortable: true,
      },
      {
        field: "actions",
        title: "Actions",
        align: "center",
        formatter: function (value, row) {
          return `
            <div class="d-flex justify-content-center gap-1">
              <button class="btn btn-sm btn-outline-warning edit-btn" data-id="${row.id}">
                <i class="fa fa-edit"></i>
              </button>

              <button class="btn btn-sm btn-outline-danger delete-btn" data-id="${row.id}" data-name="${row.name}">
                <i class="fa fa-trash"></i>
              </button>
            </div>
          `;
        },
      },
      {
        field: "created_at",
        title: "Created At",
        sortable: true,
        visible: false,
      },
      {
        field: "updated_at",
        title: "Updated At",
        sortable: true,
        visible: false,
      },
    ],
  });

  $table.on("click.permissionActions", ".edit-btn", function (e) {
    e.preventDefault();
    e.stopPropagation();

    const id = $(this).data("id");
    openEditModal(id);
  });

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
            $(tableRef.value).bootstrapTable("refresh");
          },
        });
      }
    });
  });
}

onMounted(async () => {
  await nextTick();
  initTable();
});

onBeforeUnmount(() => {
  if (!tableRef.value) return;

  const $table = $(tableRef.value);

  try {
    $table.off(".permissionActions");
    $table.off(".bs.table");
    $table.bootstrapTable("destroy");
  } catch (e) {
    //
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

    <PermissionModal
      :show="showPermissionModal"
      :mode="modalMode"
      :permission="selectedPermission"
      @close="closePermissionModal"
      @saved="handleSaved"
    />
  </div>
</template>