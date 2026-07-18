<template>
  <div class="chatify:space-y-3 chatify:py-1">
    <div class="chatify:flex chatify:gap-4">
      <div v-for="n in 3" :key="n" class="chatify:h-4 chatify:w-12 chatify:animate-pulse chatify:rounded chatify:bg-chatify-border" />
    </div>
    <div class="chatify:grid chatify:grid-cols-3 chatify:gap-1">
      <div
        v-for="n in 12"
        :key="n"
        class="chatify:aspect-square chatify:animate-pulse chatify:bg-chatify-border"
      />
    </div>
  </div>
</template>
