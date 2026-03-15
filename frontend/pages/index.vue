<template>
  <main class="content">
    <h1>Brettspiel-Ausleihplattform</h1>
    <p>Entdecke unsere Spielesammlung und leihe dein Lieblingsspiel aus.</p>
    <button class="button-primary" @click="navigateTo('/games')">Alle Spiele</button>
    <button @click="navigateTo('/login')">Anmelden</button>
  </main>
</template>
