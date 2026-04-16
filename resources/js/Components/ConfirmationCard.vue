<script setup>
defineProps({
  icon: {
    type: String,
    default: 'bx bx-check-circle',
  },
  iconBg: {
    type: String,
    default: 'bg-light',
  },
  iconColor: {
    type: String,
    default: 'text-primary',
  },
  title: {
    type: String,
    default: 'Success!',
  },
  message: {
    type: String,
    default: '',
  },
  buttonText: {
    type: String,
    default: 'Back to Home',
  },
  buttonHref: {
    type: String,
    default: '/',
  },
  buttonVariant: {
    type: String,
    default: 'success',
  },
})
</script>

<template>
  <div class="card">
    <div class="card-body">
      <div class="p-2">
        <div class="text-center">
          <div class="avatar-md mx-auto">
            <div class="avatar-title rounded-circle" :class="iconBg">
              <i class="h1 mb-0" :class="[icon, iconColor]"></i>
            </div>
          </div>
          <div class="p-2 mt-4">
            <h4>{{ title }}</h4>
            <p v-if="message" class="text-muted">{{ message }}</p>
            <slot />
            <div class="mt-4">
              <a :href="buttonHref" class="btn" :class="`btn-${buttonVariant}`">
                {{ buttonText }}
              </a>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>
