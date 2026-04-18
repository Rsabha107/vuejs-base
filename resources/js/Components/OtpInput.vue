<script setup>
import { ref, watch } from 'vue'

const props = defineProps({
  length: { type: Number, default: 4 },
  modelValue: { type: String, default: '' },
  hasError: { type: Boolean, default: false },
})

const emit = defineEmits(['update:modelValue', 'complete'])

const digits = ref(Array(props.length).fill(''))
const inputs = ref([])

watch(() => props.modelValue, (val) => {
  if (!val) digits.value = Array(props.length).fill('')
})

function onInput(index, event) {
  const val = event.target.value.replace(/\D/g, '').slice(-1)
  digits.value[index] = val
  syncModel()
  if (val && index < props.length - 1) inputs.value[index + 1]?.focus()
}

function onKeydown(index, event) {
  if (event.key === 'Backspace') {
    if (digits.value[index]) {
      digits.value[index] = ''
      syncModel()
    } else if (index > 0) {
      inputs.value[index - 1]?.focus()
    }
  } else if (event.key === 'ArrowLeft' && index > 0) {
    inputs.value[index - 1]?.focus()
  } else if (event.key === 'ArrowRight' && index < props.length - 1) {
    inputs.value[index + 1]?.focus()
  }
}

function onPaste(event) {
  event.preventDefault()
  const pasted = event.clipboardData.getData('text').replace(/\D/g, '').slice(0, props.length)
  pasted.split('').forEach((char, i) => { digits.value[i] = char })
  syncModel()
  const nextEmpty = digits.value.findIndex(d => !d)
  const focusIndex = nextEmpty === -1 ? props.length - 1 : nextEmpty
  inputs.value[focusIndex]?.focus()
}

function syncModel() {
  const value = digits.value.join('')
  emit('update:modelValue', value)
  if (value.length === props.length) emit('complete', value)
}

function focus() {
  inputs.value[0]?.focus()
}

defineExpose({ focus })
</script>

<template>
  <div class="d-flex gap-2 justify-content-center">
    <template v-for="(_, index) in length" :key="index">
      <input
        :ref="el => inputs[index] = el"
        :value="digits[index]"
        :class="['form-control form-control-lg text-center fw-semibold fs-4', { 'is-invalid': hasError }]"
        style="width: 56px; max-width: 56px; background-image: none; color: #212529; padding-right: 0.75rem;"
        maxlength="1"
        inputmode="numeric"
        autocomplete="one-time-code"
        @input="onInput(index, $event)"
        @keydown="onKeydown(index, $event)"
        @paste="onPaste"
        @focus="$event.target.select()"
      />
    </template>
  </div>
</template>
