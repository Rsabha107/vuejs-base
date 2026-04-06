<script setup>
import { ref, watch, computed, onMounted } from "vue";
import { useForm } from "@inertiajs/vue3";
import axios from "axios";

import BaseModal from "@/Components/common/BaseModal.vue";
import UserFormFields from "@/Components/forms/UserFormFields.vue";

const statuses = ref([]);

const props = defineProps({
  show: {
    type: Boolean,
    default: false,
  },
  mode: {
    type: String,
    default: "create",
  },
  user: {
    type: Object,
    default: null,
  },
});

const emit = defineEmits(["close", "saved"]);

const isEdit = computed(() => props.mode === "edit");

const form = useForm({
  id: null,
  name: "",
  email: "",
  password: "",
  status_id: "",
});

async function loadStatuses() {
  try {
    const res = await axios.get(route("global.statuses.get"));
    statuses.value = res.data ?? [];
  } catch (e) {
    console.error("Failed to load statuses", e);
  }
}

onMounted(() => {
  loadStatuses();
});

watch(
  () => [props.show, props.user, props.mode],
  () => {
    form.clearErrors();
    form.reset();

    form.id = props.user?.id ?? null;
    form.name = props.user?.name ?? "";
    form.email = props.user?.email ?? "";
    form.password = "";
    form.status_id = isEdit.value ? Number(props.user?.status_id ?? "") : "";
  },
  { immediate: true }
);

function closeModal() {
  emit("close");
}

function submit() {
  if (isEdit.value) {
    form.put(route("users.update", form.id), {
      preserveScroll: true,
      onSuccess: () => {
        if (window.toastr) window.toastr.success("User updated successfully");
        emit("saved");
      },
    });
  } else {
    form.post(route("users.store"), {
      preserveScroll: true,
      onSuccess: () => {
        if (window.toastr) window.toastr.success("User created successfully");
        emit("saved");
      },
    });
  }
}
</script>

<template>
  <BaseModal
    :show="show"
    :title="isEdit ? 'Edit User' : 'Add User'"
    width="560px"
    @close="closeModal"
  >
    <form @submit.prevent="submit">
      <UserFormFields
        :form="form"
        :statuses="statuses"
        :is-edit="isEdit"
      />

      <div class="d-flex justify-content-end gap-2">
        <button type="button" class="btn btn-light" @click="closeModal">
          Cancel
        </button>

        <button
          type="submit"
          class="btn btn-primary"
          :disabled="form.processing"
        >
          {{ form.processing ? "Saving..." : isEdit ? "Update" : "Save" }}
        </button>
      </div>
    </form>
  </BaseModal>
</template>