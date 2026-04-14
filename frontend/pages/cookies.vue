<template>
  <main class="content">
    <NuxtLink to="/" class="back-link">{{ $t('common.back_to_home') }}</NuxtLink>
    <div class="card">
      <div class="header"><h1>{{ $t('legal.cookies.title') }}</h1></div>
      <div class="body">
        <div class="alert alert-default">
          <p>{{ $t('legal.cookies.unavailable') }}</p>
        </div>
      </div>
    </div>
  </main>
</template>
