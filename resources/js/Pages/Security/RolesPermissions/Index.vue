<script setup>
// import { onMounted, nextTick } from "vue";
import { Head } from "@inertiajs/vue3";
// import ApexCharts from "apexcharts";

import HeaderNavbar from "@/Components/HeaderNavbar.vue";
import VerticalMenu from "@/Components/VerticalMenu.vue";
import PermissionBtTable from "@/Components/security/RolesHasPermissionBtTable.vue";

defineProps({
  permissions: {
    type: Array,
    default: () => [],
  },
});
</script>

<template>
  <Head title="Roles & Permissions -- vue" />

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
    <!-- Left Sidebar End -->
    <!-- ============================================================== -->
    <!-- Start right Content here -->
    <!-- ============================================================== -->
    <div class="main-content">
      <div class="page-content">
        <div class="container-fluid">
          <PermissionBtTable :rows="permissions" />
        </div>
      </div>
      <!-- end modal -->
    </div>
    <!-- end main content-->
  </div>
</template>