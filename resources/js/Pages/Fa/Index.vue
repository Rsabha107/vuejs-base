<script setup>
import { Head } from "@inertiajs/vue3";
import HeaderNavbar from "@/Components/HeaderNavbar.vue";
import VerticalMenu from "@/Components/VerticalMenu.vue";
import FaBtTable from "@/Components/ems/FaBtTable.vue";
</script>

<template>
  <Head title="Functional Areas" />

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
          <FaBtTable />
        </div>
      </div>
    </div>
  </div>
</template>
