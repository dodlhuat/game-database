<template>
  <main>
    <h1>Brettspiel-Ausleihplattform</h1>
    <p>Entdecke unsere Spielesammlung und leihe dein Lieblingsspiel aus.</p>
    <UiButton @click="navigateTo('/games')">Alle Spiele</UiButton>
    <UiButton variant="secondary" @click="navigateTo('/login')">Anmelden</UiButton>
  </main>
</template>
