<script setup>
// import { onMounted, nextTick } from "vue";
import { Head } from "@inertiajs/vue3";
// import ApexCharts from "apexcharts";

import HeaderNavbar from "@/Components/HeaderNavbar.vue";
import VerticalMenu from "@/Components/VerticalMenu.vue";
import RoleBtTable from "@/Components/security/RoleBtTable.vue";

defineProps({
  roles: {
    type: Array,
    default: () => [],
  },
});
</script>

<template>
  <Head title="Roles -- vue" />

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
          <!-- <div class="card">
            <div class="card-body"> -->
              <RoleBtTable :rows="roles" />
            <!-- </div>
          </div> -->
        </div>
      </div>
      <!-- end modal -->
    </div>
    <!-- end main content-->
  </div>
</template>