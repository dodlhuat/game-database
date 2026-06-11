<template>
  <div>
    <NuxtRouteAnnouncer />
    <NuxtLoadingIndicator color="var(--accent-color)" :height="2" :duration="400" :throttle="0" />
    <NuxtLayout>
      <NuxtPage />
    </NuxtLayout>
  </div>
</template>
