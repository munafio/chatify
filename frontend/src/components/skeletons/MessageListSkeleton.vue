<template>
  <div class="chatify:space-y-3 chatify:p-4">
    <div
      v-for="n in 6"
      :key="n"
      class="chatify:flex"
      :class="n % 2 === 0 ? 'chatify:justify-end' : 'chatify:justify-start'"
    >
      <div
        class="chatify:animate-pulse chatify:rounded-2xl chatify:bg-chatify-border/70"
        :class="n % 2 === 0 ? 'chatify:h-14 chatify:w-[58%]' : 'chatify:h-10 chatify:w-[42%]'"
      />
    </div>
  </div>
</template>
