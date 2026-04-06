<script setup>
import { ref, onMounted, onBeforeUnmount, watch, nextTick } from "vue";
import $ from "jquery";

const props = defineProps({
  rows: {
    type: Array,
    default: () => [],
  },
});

const tableRef = ref(null);

function actionFormatter(_value, row) {
  return `
    <div class="action-group">
      <button class="btn-action btn-edit edit-user" data-id="${row.id}" title="Edit">
        <i class="fa fa-pencil-alt"></i>
      </button>
      <button class="btn-action btn-delete delete-user" data-id="${row.id}" title="Delete">
        <i class="fa fa-trash"></i>
      </button>
    </div>
  `;
}

function initTable() {
  if (!tableRef.value) return;

  const $table = $(tableRef.value);

  $table.off("click-row.bs.table");
  $table.off("click", ".edit-user");
  $table.off("click", ".delete-user");

  try {
    $table.bootstrapTable("destroy");
  } catch (e) {
    //
  }

  $table.bootstrapTable({
    url: "/api/users",
    method: "get",
    // data: props.rows,
    classes: "table table-borderless align-middle prime-table",
    theadClasses: "prime-table-head",
    pagination: true,
    sidePagination: "server",
    search: true,
    searchAlign: "left",
    buttonsAlign: "right",
    toolbarAlign: "right",
    showRefresh: true,
    showColumns: true,
    paginationLoop: false,
    pageList: [10, 25, 50, 100],
    pageSize: 10,
    height: 600,
    striped: false,
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
      };
    },
    responseHandler(res) {
      // if your API already returns { total, rows } keep this
      return res;
    },
    columns: [
      {
        field: "id",
        title: "ID",
        sortable: true,
        width: 90,
        cellStyle: () => ({
          classes: "fw-semibold text-muted",
        }),
      },
      {
        field: "name",
        title: "Name",
        sortable: true,
        cellStyle: () => ({
          classes: "fw-semibold text-dark",
        }),
      },
      {
        field: "email",
        title: "Email",
        sortable: true,
        cellStyle: () => ({
          classes: "text-muted",
        }),
      },
      {
        field: "actions",
        title: "Actions",
        align: "center",
        clickToSelect: false,
        formatter: actionFormatter,
        width: 160,
      },
    ],
  });

  $table.on("click-row.bs.table", function (_e, row) {
    console.log("clicked row:", row);
  });

  $table.on("click", ".edit-user", function (e) {
    e.stopPropagation();
    const id = $(this).data("id");
    console.log("edit user", id);
  });

  $table.on("click", ".delete-user", function (e) {
    e.stopPropagation();
    const id = $(this).data("id");
    console.log("delete user", id);
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
    //
  }
});
</script>

<template>
  <div class="prime-card">
    <div class="prime-card-header">
      <div>
        <h4 class="prime-title mb-1">Users</h4>
        <p class="prime-subtitle mb-0">Manage your user records</p>
      </div>
    </div>

    <div class="prime-table-shell">
      <table ref="tableRef"></table>
    </div>
  </div>
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
  margin-bottom: 1rem;
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

.prime-table-shell :deep(.fixed-table-container) {
  border: 1px solid #e5e7eb;
  border-radius: 16px;
  overflow: hidden;
  background: #fff;
}

.prime-table-shell :deep(.fixed-table-toolbar) {
  padding: 0 0 1rem 0;
}

.prime-table-shell :deep(.fixed-table-toolbar .search) {
  margin-bottom: 0.5rem;
}

.prime-table-shell :deep(.fixed-table-toolbar .search input) {
  height: 42px;
  min-width: 260px;
  border-radius: 12px;
  border: 1px solid #dbe3ec;
  box-shadow: none;
  padding-left: 0.9rem;
}

.prime-table-shell :deep(.fixed-table-toolbar .search input:focus) {
  border-color: #86b7fe;
  box-shadow: 0 0 0 0.15rem rgba(13, 110, 253, 0.12);
}

.prime-table-shell :deep(.fixed-table-toolbar .columns),
.prime-table-shell :deep(.fixed-table-toolbar .btn-secondary) {
  border-radius: 12px !important;
}

.prime-table-shell :deep(.fixed-table-toolbar .btn) {
  height: 42px;
  border-radius: 12px;
  border: 1px solid #dbe3ec;
  background: #fff;
  color: #475467;
  box-shadow: none;
  padding: 0 14px;
}

.prime-table-shell :deep(.fixed-table-toolbar .btn:hover) {
  background: #f8fafc;
  border-color: #cfd8e3;
}

.prime-table-shell :deep(.table) {
  margin-bottom: 0 !important;
}

.prime-table-shell :deep(.table thead th) {
  background: #f8fafc !important;
  color: #344054 !important;
  font-size: 14px;
  font-weight: 700;
  padding: 15px 16px !important;
  border-bottom: 1px solid #e5e7eb !important;
  border-top: 0 !important;
}

.prime-table-shell :deep(.table tbody td) {
  padding: 15px 16px !important;
  vertical-align: middle !important;
  border-top: 1px solid #f1f5f9 !important;
  color: #111827;
  background: #fff;
}

.prime-table-shell :deep(.table tbody tr:hover td) {
  background: #f9fbff !important;
  transition: background 0.2s ease;
}

.prime-table-shell :deep(.fixed-table-pagination) {
  padding-top: 1rem;
}

.prime-table-shell :deep(.pagination) {
  margin-bottom: 0;
}

.prime-table-shell :deep(.page-item .page-link) {
  border-radius: 10px !important;
  margin: 0 2px;
  border: 1px solid #e5e7eb;
  color: #344054;
  min-width: 36px;
  text-align: center;
}

.prime-table-shell :deep(.page-item.active .page-link) {
  background: #0d6efd;
  border-color: #0d6efd;
  color: #fff;
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
</style>