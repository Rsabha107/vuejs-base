<script setup>
import { Head } from "@inertiajs/vue3";
import HeaderNavbar from "@/Components/HeaderNavbar.vue";
import VerticalMenu from "@/Components/VerticalMenu.vue";
import VenueBtTable from "@/Components/ems/VenueBtTable.vue";
</script>

<template>
  <Head title="Venues" />

  <div id="layout-wrapper">
    <header id="page-topbar">
      <HeaderNavbar />
    </header>

    <div class="vertical-menu">
      <VerticalMenu />
    </div>

    <div class="main-content">
      <div class="page-content">
        <div class="container-fluid">
          <VenueBtTable />
        </div>
      </div>
    </div>
  </div>
</template>
