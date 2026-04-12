<script setup>
import { ref, watch, computed } from "vue";
import axios from "axios";
import { useForm } from "@inertiajs/vue3";

import vueFilePond from "vue-filepond/dist/vue-filepond.esm.js";
import FilePondPluginImagePreview from "filepond-plugin-image-preview";
import FilePondPluginFileValidateType from "filepond-plugin-file-validate-type";

import "filepond/dist/filepond.min.css";
import "filepond-plugin-image-preview/dist/filepond-plugin-image-preview.css";

const FilePond = vueFilePond(
  FilePondPluginImagePreview,
  FilePondPluginFileValidateType
);

const statuses    = ref([]);
const allVenues   = ref([]);
const venueSearch = ref("");
const pondFiles   = ref([]);

// FilePond server: only the load handler is needed (to display the existing image)
const pondServer = {
  load: (source, load, error) => {
    fetch(source)
      .then((res) => res.blob())
      .then(load)
      .catch(error);
  },
};

const props = defineProps({
  show:  { type: Boolean, default: false },
  mode:  { type: String,  default: "create" },
  event: { type: Object,  default: null },
});

const emit = defineEmits(["close", "saved"]);

const isEdit = computed(() => props.mode === "edit");

const form = useForm({
  id:          null,
  name:        "",
  active_flag: "",
  logo:        null,
  venue_ids:   [],
});

const filteredVenues = computed(() =>
  venueSearch.value.trim()
    ? allVenues.value.filter((v) =>
        v.title.toLowerCase().includes(venueSearch.value.toLowerCase())
      )
    : allVenues.value
);

async function loadStatuses() {
  if (statuses.value.length) return;
  try {
    const res = await axios.get(route("global.statuses.get"));
    statuses.value = res.data ?? [];
  } catch (e) {
    console.error("Failed to load statuses", e);
  }
}

async function loadVenues() {
  if (allVenues.value.length) return;
  try {
    const res = await axios.get(route("venues.all"));
    allVenues.value = res.data ?? [];
  } catch (e) {
    console.error("Failed to load venues", e);
  }
}

watch(
  () => props.show,
  async (val) => {
    if (!val) return;
    await Promise.all([loadStatuses(), loadVenues()]);

    venueSearch.value = "";
    form.clearErrors();
    form.reset();
    form.id          = props.event?.id ?? null;
    form.name        = props.event?.name ?? "";
    form.active_flag = props.event?.active_flag ? Number(props.event.active_flag) : "";
    form.logo        = null;
    form.venue_ids   = props.event?.venue_ids ?? [];

    if (isEdit.value && props.event?.logo_url) {
      pondFiles.value = [{ source: props.event.logo_url, options: { type: "local" } }];
    } else {
      pondFiles.value = [];
    }
  }
);

function onAddFile(_err, fileItem) {
  form.logo = fileItem.file;
}

function onRemoveFile() {
  form.logo = null;
}

function closeModal() {
  emit("close");
}

function submit() {
  const onSuccess = () => {
    window.toastr?.success(
      isEdit.value ? "Event updated successfully" : "Event created successfully"
    );
    emit("saved");
  };

  const options = { preserveScroll: true, forceFormData: true, onSuccess };

  if (isEdit.value) {
    form.post(route("events.update", props.event.id), options);
  } else {
    form.post(route("events.store"), options);
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
            <i class="fa fa-calendar-alt me-2 text-primary"></i>
            {{ isEdit ? "Edit Event" : "Add Event" }}
          </h5>
          <button class="btn-close" @click="closeModal"></button>
        </div>

        <!-- Body -->
        <div class="ev-modal-body">
          <form id="event-form" @submit.prevent="submit">
            <!-- Logo -->
            <div class="mb-3">
              <label class="form-label fw-semibold">Event Logo</label>
              <FilePond
                name="logo"
                class="ev-filepond"
                :server="pondServer"
                label-idle='<i class="fa fa-image me-1"></i> Drop image or <span class="filepond--label-action">Browse</span>'
                accepted-file-types="image/*"
                :allow-multiple="false"
                :files="pondFiles"
                @addfile="onAddFile"
                @removefile="onRemoveFile"
              />
              <div v-if="form.errors.logo" class="text-danger small mt-1">
                {{ form.errors.logo }}
              </div>
            </div>

            <!-- Name -->
            <div class="mb-3">
              <label class="form-label fw-semibold">Event Name</label>
              <input
                v-model="form.name"
                type="text"
                class="form-control"
                :class="{ 'is-invalid': form.errors.name }"
                placeholder="Enter event name"
              />
              <div v-if="form.errors.name" class="invalid-feedback">
                {{ form.errors.name }}
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

            <!-- Venues -->
            <div class="mb-3">
              <div class="d-flex justify-content-between align-items-center mb-2">
                <label class="form-label fw-semibold mb-0">
                  Venues
                  <span class="ev-badge-count">{{ form.venue_ids.length }} selected</span>
                </label>
                <button
                  type="button"
                  class="btn btn-link btn-sm p-0 text-muted text-decoration-none"
                  @click="form.venue_ids = []"
                >
                  Clear all
                </button>
              </div>
              <input
                v-model="venueSearch"
                type="text"
                class="form-control form-control-sm mb-2"
                placeholder="Search venues…"
              />
              <div class="ev-venue-list">
                <label
                  v-for="v in filteredVenues"
                  :key="v.id"
                  class="ev-venue-item"
                  :class="{ 'is-checked': form.venue_ids.includes(v.id) }"
                >
                  <input
                    type="checkbox"
                    :value="v.id"
                    v-model="form.venue_ids"
                    class="ev-venue-checkbox"
                  />
                  <span>{{ v.title }}</span>
                </label>
                <div v-if="filteredVenues.length === 0" class="text-muted small py-3 text-center">
                  No venues found.
                </div>
              </div>
              <div v-if="form.errors.venue_ids" class="text-danger small mt-1">
                {{ form.errors.venue_ids }}
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
            form="event-form"
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

/* Venue picker */
.ev-badge-count {
  display: inline-block;
  background: #eef2ff;
  color: #3a5bd9;
  font-size: 11px;
  font-weight: 700;
  border-radius: 50px;
  padding: 1px 8px;
  margin-left: 6px;
}

.ev-venue-list {
  max-height: 160px;
  overflow-y: auto;
  border: 1px solid #eaecf6;
  border-radius: 12px;
  padding: 6px 4px;
}

.ev-venue-item {
  display: flex;
  align-items: center;
  gap: 10px;
  padding: 7px 12px;
  border-radius: 8px;
  cursor: pointer;
  transition: background 0.12s;
  font-size: 13px;
  color: #374151;
  user-select: none;
}

.ev-venue-item:hover { background: #f5f7ff; }

.ev-venue-item.is-checked {
  background: #eef2ff;
  color: #3a5bd9;
  font-weight: 500;
}

.ev-venue-checkbox {
  width: 16px;
  height: 16px;
  border-radius: 4px;
  border-color: #5b8df6;
  flex-shrink: 0;
  cursor: pointer;
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
