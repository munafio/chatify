<template>
  <div class="chatify:space-y-3 chatify:py-1">
    <div class="chatify:h-10 chatify:animate-pulse chatify:rounded-lg chatify:bg-chatify-border" />
    <div
      v-for="n in 6"
      :key="n"
      class="chatify:flex chatify:items-center chatify:gap-3 chatify:py-3"
    >
      <div class="chatify:h-10 chatify:w-10 chatify:animate-pulse chatify:rounded-full chatify:bg-chatify-border" />
      <div class="chatify:flex-1 chatify:space-y-2">
        <div class="chatify:h-3 chatify:w-2/3 chatify:animate-pulse chatify:rounded chatify:bg-chatify-border" />
        <div class="chatify:h-3 chatify:w-1/3 chatify:animate-pulse chatify:rounded chatify:bg-chatify-border" />
      </div>
    </div>
  </div>
</template>
