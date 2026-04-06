<script setup>
const props = defineProps({
  show: {
    type: Boolean,
    default: false,
  },
  title: {
    type: String,
    default: "Modal",
  },
  width: {
    type: String,
    default: "560px",
  },
});

const emit = defineEmits(["close"]);

function closeModal() {
  emit("close");
}

function onBackdropClick(e) {
  if (e.target === e.currentTarget) {
    closeModal();
  }
}
</script>

<template>
  <div
    v-if="show"
    class="custom-modal-backdrop"
    @click="onBackdropClick"
  >
    <div class="custom-modal-card" :style="{ maxWidth: width }">
      <div class="d-flex justify-content-between align-items-center mb-3">
        <h5 class="mb-0">{{ title }}</h5>
        <button type="button" class="btn-close" @click="closeModal"></button>
      </div>

      <slot />
    </div>
  </div>
</template>

<style scoped>
.custom-modal-backdrop {
  position: fixed;
  inset: 0;
  background: rgba(15, 23, 42, 0.45);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 2000;
  padding: 1rem;
}

.custom-modal-card {
  width: 100%;
  background: #fff;
  border-radius: 16px;
  padding: 1.25rem;
  box-shadow: 0 20px 60px rgba(0, 0, 0, 0.2);
}
</style>