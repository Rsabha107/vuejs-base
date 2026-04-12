<script setup>
import { ref, watch, computed } from "vue";
import { router } from "@inertiajs/vue3";

const props = defineProps({
  show: {
    type: Boolean,
    default: false,
  },
  mode: {
    type: String,
    default: "create", // create | edit
  },
  permission: {
    type: Object,
    default: null,
  },
});

const emit = defineEmits(["close", "saved"]);

const form = ref({
  id: null,
  name: "",
});

const errors = ref({});
const saving = ref(false);

const isEdit = computed(() => props.mode === "edit");
const title = computed(() => (isEdit.value ? "Edit Permission" : "Add Permission"));
const submitText = computed(() => {
  if (saving.value) return isEdit.value ? "Updating..." : "Saving...";
  return isEdit.value ? "Update" : "Save";
});

watch(
  () => [props.show, props.permission, props.mode],
  () => {
    errors.value = {};
    saving.value = false;

    form.value = {
      id: props.permission?.id ?? null,
      name: props.permission?.name ?? "",
    };
  },
  { immediate: true, deep: true }
);

function closeModal() {
  emit("close");
}

function submit() {
  errors.value = {};
  saving.value = true;

  const payload = {
    name: form.value.name,
  };

  if (isEdit.value) {
    router.put(route("permissions.update", form.value.id), payload, {
      preserveScroll: true,
      onSuccess: () => {
        saving.value = false;
        window.toastr.success("Permission updated successfully", "Success");
        emit("saved");
        closeModal();
      },
      onError: (err) => {
        saving.value = false;
        errors.value = err;

        window.toastr.error("Failed to update permission", "Error");
      },
    });
  } else {
    router.post(route("permissions.store"), payload, {
      preserveScroll: true,
      onSuccess: () => {
        saving.value = false;
        window.toastr.success("Permission created successfully", "Success");
        emit("saved");
        closeModal();
      },
      onError: (err) => {
        saving.value = false;
        errors.value = err;
        window.toastr.error("Failed to create permission", "Error");
      },
    });
  }
}
</script>

<template>
  <Teleport to="body">
    <div v-if="show" class="rp-modal-overlay" @click.self="closeModal">
      <div class="rp-modal-box">
        <!-- Header -->
        <div class="rp-modal-header">
          <h5 class="rp-modal-title">
            <i class="fa fa-user me-2 text-primary"></i>
            {{ isEdit ? "Edit Permission" : "Add Permission" }}
          </h5>
          <button class="btn-close" @click="closeModal"></button>
        </div>

        <!-- Body -->
        <div class="rp-modal-body">
          <form id="permission-form" @submit.prevent="submit">
            <div class="mb-3">
              <label class="form-label" for="permission-name">Name</label>
              <input
                id="permission-name"
                v-model="form.name"
                type="text"
                class="form-control"
                :class="{ 'is-invalid': errors.name }"
                placeholder="Enter permission name"
              />
              <div v-if="errors.name" class="invalid-feedback d-block">
                {{ errors.name }}
              </div>
            </div>
          </form>
        </div>

        <!-- Footer -->
        <div class="rp-modal-footer">
          <button type="button" class="btn btn-light" @click="closeModal">
            Cancel
          </button>
          <button
            type="submit"
            form="permission-form"
            class="btn btn-primary"
            :disabled="saving"
          >
            {{ submitText }}
          </button>
        </div>
      </div>
    </div>
  </Teleport>
</template>

<style scoped>
/* ═══════════════════════════════════════════════════════════════
   Modal
═══════════════════════════════════════════════════════════════ */
.rp-modal-overlay {
  position: fixed;
  inset: 0;
  background: rgba(0, 0, 0, 0.45);
  z-index: 1050;
  display: flex;
  align-items: center;
  justify-content: center;
}

.rp-modal-box {
  background: #fff;
  border-radius: 18px;
  width: 520px;
  max-width: 95vw;
  max-height: 90vh;
  display: flex;
  flex-direction: column;
  box-shadow: 0 20px 60px rgba(0, 0, 0, 0.22);
}

.rp-modal-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 1rem 1.25rem;
  border-bottom: 1px solid #eaecf6;
  flex-shrink: 0;
}

.rp-modal-title {
  margin: 0;
  font-size: 1rem;
  font-weight: 700;
  color: #1f2937;
}

.rp-modal-body {
  padding: 1.25rem;
  overflow-y: auto;
  flex: 1 1 auto;
}

.rp-modal-footer {
  padding: 1rem 1.25rem;
  border-top: 1px solid #eaecf6;
  display: flex;
  justify-content: flex-end;
  gap: 0.5rem;
  flex-shrink: 0;
}
</style>
