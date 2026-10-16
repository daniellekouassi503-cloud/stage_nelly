<template>
  <div class="main-wrapper">
    <div class="container py-5">
      <div class="row justify-content-center">
        <div class="col-md-6 col-lg-5">
          <div class="text-center mb-4">
            <h3 class="marque-texte mb-1">Plateforme scolaire</h3>
            <p class="text-muted mb-0">Suivi des notes, des supports et des frais</p>
          </div>
          <div class="card">
            <div class="card-body p-4">
              <slot />
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>
