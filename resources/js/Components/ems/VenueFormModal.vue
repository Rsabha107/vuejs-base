<script setup>
import { ref, watch, computed } from "vue";
import axios from "axios";
import { useForm } from "@inertiajs/vue3";

const statuses  = ref([]);


const props = defineProps({
  show:  { type: Boolean, default: false },
  mode:  { type: String,  default: "create" },
  venue: { type: Object,  default: null },
});

const emit = defineEmits(["close", "saved"]);

const isEdit = computed(() => props.mode === "edit");

const form = useForm({
  id:          null,
  title:       "",
  active_flag: "",
});

async function loadStatuses() {
  if (statuses.value.length) return;
  try {
    const res = await axios.get(route("global.statuses.get"));
    statuses.value = res.data ?? [];
  } catch (e) {
    console.error("Failed to load statuses", e);
  }
}

watch(
  () => props.show,
  async (val) => {
    if (!val) return;
    await loadStatuses();

    form.clearErrors();
    form.reset();
    form.id          = props.venue?.id ?? null;
    form.title       = props.venue?.title ?? "";
    form.active_flag = props.venue?.active_flag ? Number(props.venue.active_flag) : "";

  }
);

function closeModal() {
  emit("close");
}

function submit() {
  const onSuccess = () => {
    window.toastr?.success(
      isEdit.value ? "Venue updated successfully" : "Venue created successfully"
    );
    emit("saved");
  };

  const options = { preserveScroll: true, forceFormData: true, onSuccess };

  if (isEdit.value) {
    form.post(route("venues.update", props.venue.id), options);
  } else {
    form.post(route("venues.store"), options);
  }
}
</script>

<template>
  <Teleport to="body">
    <div v-if="show" class="ev-modal-overlay" @click.self="closeModal">
      <div class="ev-modal-box">
        <!-- Header -->
        <div class="ev-modal-header">
          <h5 class="ev-modal-title">
            <i class="fa fa-map-marker-alt me-2 text-primary"></i>
            {{ isEdit ? "Edit Venue" : "Add Venue" }}
          </h5>
          <button class="btn-close" @click="closeModal"></button>
        </div>

        <!-- Body -->
        <div class="ev-modal-body">
          <form id="venue-form" @submit.prevent="submit">

            <!-- Title -->
            <div class="mb-3">
              <label class="form-label fw-semibold">Venue Title</label>
              <input
                v-model="form.title"
                type="text"
                class="form-control"
                :class="{ 'is-invalid': form.errors.title }"
                placeholder="Enter venue title"
              />
              <div v-if="form.errors.title" class="invalid-feedback">
                {{ form.errors.title }}
              </div>
            </div>

            <!-- Status -->
            <div class="mb-3">
              <label class="form-label fw-semibold">Status</label>
              <select
                v-model="form.active_flag"
                class="form-select"
                :class="{ 'is-invalid': form.errors.active_flag }"
              >
                <option value="">-- Select Status --</option>
                <option v-for="s in statuses" :key="s.id" :value="s.id">
                  {{ s.name }}
                </option>
              </select>
              <div v-if="form.errors.active_flag" class="invalid-feedback">
                {{ form.errors.active_flag }}
              </div>
            </div>
          </form>
        </div>

        <!-- Footer -->
        <div class="ev-modal-footer">
          <button type="button" class="btn btn-light" @click="closeModal">
            Cancel
          </button>
          <button
            type="submit"
            form="venue-form"
            class="btn btn-primary"
            :disabled="form.processing"
          >
            <span
              v-if="form.processing"
              class="spinner-border spinner-border-sm me-1"
            ></span>
            {{ form.processing ? "Saving..." : isEdit ? "Update" : "Save" }}
          </button>
        </div>
      </div>
    </div>
  </Teleport>
</template>

<style scoped>
.ev-modal-overlay {
  position: fixed;
  inset: 0;
  background: rgba(0, 0, 0, 0.45);
  z-index: 1050;
  display: flex;
  align-items: center;
  justify-content: center;
}

.ev-modal-box {
  background: #fff;
  border-radius: 18px;
  width: 480px;
  max-width: 95vw;
  max-height: 90vh;
  display: flex;
  flex-direction: column;
  box-shadow: 0 20px 60px rgba(0, 0, 0, 0.22);
}

.ev-modal-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 1rem 1.25rem;
  border-bottom: 1px solid #eaecf6;
  flex-shrink: 0;
}

.ev-modal-title {
  margin: 0;
  font-size: 1rem;
  font-weight: 700;
  color: #1f2937;
}

.ev-modal-body {
  padding: 1.25rem;
  overflow-y: auto;
  flex: 1 1 auto;
}

.ev-modal-footer {
  padding: 1rem 1.25rem;
  border-top: 1px solid #eaecf6;
  display: flex;
  justify-content: flex-end;
  gap: 0.5rem;
  flex-shrink: 0;
}

/* FilePond tweaks */
:deep(.ev-filepond .filepond--root) {
  border-radius: 12px;
  font-family: inherit;
}

:deep(.ev-filepond .filepond--panel-root) {
  background: #f8f9ff;
  border: 1.5px dashed #c7d2fe;
  border-radius: 12px;
}

:deep(.ev-filepond .filepond--drop-label) {
  color: #6b7280;
  font-size: 13px;
}

:deep(.ev-filepond .filepond--label-action) {
  color: #3a5bd9;
  text-decoration: underline;
}
</style>
