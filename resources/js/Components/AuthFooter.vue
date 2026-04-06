<script setup>
import { Link } from "@inertiajs/vue3";

// give me the current year
const currentYear = new Date().getFullYear();
</script>
<template>
  <p>
    RS©
    <!-- <script>
     document.write(new Date().getFullYear())
    </script> -->
    {{ currentYear }}
    Skote. Crafted with
    <i class="mdi mdi-heart text-danger"> </i>
    by Themesbrand
  </p>
</template>
