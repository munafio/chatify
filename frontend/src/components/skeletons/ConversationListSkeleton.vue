<template>
  <div class="chatify:p-2">
    <div
      v-for="n in 8"
      :key="n"
      class="chatify:mb-2 chatify:flex chatify:items-center chatify:gap-3 chatify:px-2 chatify:py-3"
    >
      <div class="chatify:h-12 chatify:w-12 chatify:animate-pulse chatify:rounded-full chatify:bg-chatify-border" />
      <div class="chatify:flex-1 chatify:space-y-2">
        <div class="chatify:h-3 chatify:w-2/3 chatify:animate-pulse chatify:rounded chatify:bg-chatify-border" />
        <div class="chatify:h-3 chatify:w-1/2 chatify:animate-pulse chatify:rounded chatify:bg-chatify-border" />
      </div>
    </div>
  </div>
</template>
