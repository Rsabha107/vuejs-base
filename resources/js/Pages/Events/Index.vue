<script setup>
import { Head } from "@inertiajs/vue3";
import HeaderNavbar from "@/Components/HeaderNavbar.vue";
import VerticalMenu from "@/Components/VerticalMenu.vue";
import EventBtTable from "@/Components/ems/EventBtTable.vue";
</script>

<template>
  <Head title="Events" />

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
          <EventBtTable />
        </div>
      </div>
    </div>
  </div>
</template>
