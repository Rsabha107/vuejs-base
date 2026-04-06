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
  <div
    v-if="show"
    class="modal fade show"
    style="display: block; background: rgba(0, 0, 0, 0.4)"
    tabindex="-1"
    aria-modal="true"
    role="dialog"
  >
    <div class="modal-dialog">
      <div class="modal-content">
        <form @submit.prevent="submit">
          <div class="modal-header">
            <h5 class="modal-title">{{ title }}</h5>
            <button type="button" class="btn-close" @click="closeModal"></button>
          </div>

          <div class="modal-body">
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
          </div>

          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" @click="closeModal">
              Cancel
            </button>
            <button type="submit" class="btn btn-primary" :disabled="saving">
              {{ submitText }}
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>