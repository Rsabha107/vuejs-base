<script setup>
// import { onMounted, nextTick } from "vue";
import { Head } from "@inertiajs/vue3";
// import ApexCharts from "apexcharts";

import HeaderNavbar from "@/Components/HeaderNavbar.vue";
import VerticalMenu from "@/Components/VerticalMenu.vue";
import PermissionBtTable from "@/Components/security/PermissionBtTable.vue";

defineProps({
  permissions: {
    type: Array,
    default: () => [],
  },
});
</script>

<template>
  <Head title="Permissions -- vue" />

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
          <div class="card">
            <div class="card-body">
              <h4 class="mb-3">Permissions</h4>
              <PermissionBtTable :rows="permissions" />
            </div>
          </div>
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
    <!-- end main content-->
  </div>
</template>