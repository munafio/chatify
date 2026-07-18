<template>
  <div class="chatify:space-y-5 chatify:py-1">
    <div class="chatify:flex chatify:flex-col chatify:items-center chatify:gap-3">
      <div class="chatify:h-28 chatify:w-28 chatify:animate-pulse chatify:rounded-full chatify:bg-chatify-border" />
      <div class="chatify:h-5 chatify:w-40 chatify:animate-pulse chatify:rounded chatify:bg-chatify-border" />
      <div class="chatify:h-3 chatify:w-56 chatify:animate-pulse chatify:rounded chatify:bg-chatify-border" />
      <div class="chatify:h-3 chatify:w-32 chatify:animate-pulse chatify:rounded chatify:bg-chatify-border" />
    </div>

    <div class="chatify:flex chatify:justify-center chatify:gap-6">
      <div v-for="n in 2" :key="n" class="chatify:flex chatify:flex-col chatify:items-center chatify:gap-1">
        <div class="chatify:h-12 chatify:w-12 chatify:animate-pulse chatify:rounded-full chatify:bg-chatify-border" />
        <div class="chatify:h-3 chatify:w-14 chatify:animate-pulse chatify:rounded chatify:bg-chatify-border" />
      </div>
    </div>

    <div class="chatify:space-y-2">
      <div class="chatify:h-10 chatify:animate-pulse chatify:rounded-lg chatify:bg-chatify-border" />
      <div class="chatify:grid chatify:grid-cols-4 chatify:gap-1">
        <div
          v-for="n in 4"
          :key="n"
          class="chatify:aspect-square chatify:animate-pulse chatify:rounded chatify:bg-chatify-border"
        />
      </div>
    </div>

    <div class="chatify:space-y-1">
      <div class="chatify:mb-2 chatify:h-4 chatify:w-24 chatify:animate-pulse chatify:rounded chatify:bg-chatify-border" />
      <div
        v-for="n in 3"
        :key="n"
        class="chatify:flex chatify:items-center chatify:gap-3 chatify:py-2"
      >
        <div class="chatify:h-8 chatify:w-8 chatify:animate-pulse chatify:rounded-full chatify:bg-chatify-border" />
        <div class="chatify:h-3 chatify:flex-1 chatify:animate-pulse chatify:rounded chatify:bg-chatify-border" />
      </div>
    </div>
  </div>
</template>
