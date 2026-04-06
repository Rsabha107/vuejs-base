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
      { field: "created_at", title: "Created At" },
      { field: "updated_at", title: "Updated At" },
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
  <table ref="tableRef" class="table table-bordered table-hover"></table>
</template>