<script setup>
import { ref, watch } from "vue";
import { router } from "@inertiajs/vue3";

const props = defineProps({
  show: Boolean,
  permission: Object,
});

const emit = defineEmits(["close", "saved"]);

const form = ref({
  id: null,
  name: "",
});

const errors = ref({});
const saving = ref(false);

watch(
  () => props.permission,
  (val) => {
    form.value = {
      id: val?.id ?? null,
      name: val?.name ?? "",
    };
    errors.value = {};
  },
  { immediate: true }
);

function close() {
  emit("close");
}

function submit() {
  if (!form.value.id) return;

  saving.value = true;
  errors.value = {};

  router.put(
    route("permissions.update", form.value.id),
    {
      name: form.value.name,
    },
    {
      preserveScroll: true,
      onSuccess: () => {
        saving.value = false;
        emit("saved");

        Swal.fire({
          icon: "success",
          title: "Updated!",
          timer: 1200,
          showConfirmButton: false,
        });
      },
      onError: (err) => {
        saving.value = false;
        errors.value = err;
      },
    }
  );
}
</script>

<template>
  <div v-if="show" class="modal fade show d-block" style="background: rgba(0,0,0,.4)">
    <div class="modal-dialog">
      <div class="modal-content">
        <form @submit.prevent="submit">
          <div class="modal-header">
            <h5 class="modal-title">Edit Permission</h5>
            <button type="button" class="btn-close" @click="close"></button>
          </div>

          <div class="modal-body">
            <div class="mb-3">
              <label class="form-label">Name</label>
              <input
                v-model="form.name"
                type="text"
                class="form-control"
                :class="{ 'is-invalid': errors.name }"
              />
              <div v-if="errors.name" class="invalid-feedback d-block">
                {{ errors.name }}
              </div>
            </div>
          </div>

          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" @click="close">
              Cancel
            </button>
            <button type="submit" class="btn btn-primary" :disabled="saving">
              {{ saving ? "Saving..." : "Update" }}
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>



