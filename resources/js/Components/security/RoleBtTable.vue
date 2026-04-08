<script setup>
import { ref, onMounted, onBeforeUnmount, watch, nextTick } from "vue";
import $ from "jquery";

// const props = defineProps({
//   rows: {
//     type: Array,
//     default: () => [],
//   },
// });

const tableRef = ref(null);

function actionFormatter(value, row) {
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
}

function initTable() {
  if (!tableRef.value) return;

  const $table = $(tableRef.value);

  // Clean old instance first
  try {
    $table.bootstrapTable("destroy");
  } catch (e) {
    // ignore if not initialized yet
  }

  $table.bootstrapTable({
    url: "/api/roles",
    method: "get",
    toolbar: "#toolbar",
    pagination: true,
    sidePagination: "server",
    search: true,
    showRefresh: true,
    height: 600,
    filterControl: true,
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

        filter_id: $(".bootstrap-table-filter-control-id").val(),
        filter_name: $(".bootstrap-table-filter-control-name").val(),
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
        filterControl: "input",
      },
      { field: "name", title: "Name", sortable: true, filterControl: "input" },
      {
        field: "created_at",
        title: "Created At",
        sortable: true,
        filterControl: "input",
      },
      {
        field: "updated_at",
        title: "Updated At",
        sortable: true,
        filterControl: "input",
      },
      {
        field: "actions",
        title: "Actions",
        formatter: actionFormatter,
        events: window.roleEvents,
      },
    ],
  });

  $table.on("click-row.bs.table", function (_e, row) {
    console.log("clicked row:", row);
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
  <div class="roles-table-wrapper">
    <div class="roles-table-card">
      <div id="toolbar">
        <button id="remove" class="btn btn-danger" disabled>
          <i class="fa fa-trash"></i> Delete
        </button>
        
      </div>

      <div class="roles-table-wrapper">
        <table ref="tableRef"></table>
      </div>
    </div>
  </div>
</template>