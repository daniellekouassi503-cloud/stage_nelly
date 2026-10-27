<template>
  <div class="main-wrapper auth-epcci">
    <div class="container py-5">
      <div class="row justify-content-center">
        <div class="col-md-6 col-lg-5">
          <div class="text-center mb-4">
            <p class="sigle-epcci mb-0">EPCCI</p>
            <h3 class="marque-texte mb-1">Portail de scolarité</h3>
            <p class="text-muted mb-0">École Pratique de la Chambre de Commerce et d’Industrie</p>
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
