<template>
  <header class="topbar">
    <strong>SIRH · Onboarding</strong>
    <nav>
      <RouterLink to="/onboarding" exact-active-class="active">Tableau de bord</RouterLink>
      <RouterLink to="/onboarding/checklist" active-class="active">Checklist</RouterLink>
      <RouterLink to="/onboarding/suivi" active-class="active">Suivi salariés</RouterLink>
      <RouterLink to="/onboarding/items/nouveau" active-class="active">Nouvel item</RouterLink>
      <RouterLink to="/onboarding/validation" active-class="active">Validation</RouterLink>
      <RouterLink to="/onboarding/historique" active-class="active">Historique</RouterLink>
    </nav>
  </header>
  <main class="container">
    <RouterView />
  </main>
</template>
