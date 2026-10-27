<template>
  <div class="accueil-eduan">
    <div class="sidebar-info side-info" :class="{ 'info-open': menuMobile }">
      <div class="sidebar-logo-wrapper mb-25">
        <div class="row align-items-center">
          <div class="col-8">
            <div class="sidebar-logo">
              <router-link to="/" @click="menuMobile = false">
                <img src="/accueil/images/epcci/logo.jpg" alt="EPCCI" class="logo-epcci">
              </router-link>
            </div>
          </div>
          <div class="col-4 text-end">
            <button class="sidebar-close side-info-close" type="button" aria-label="Fermer" @click="menuMobile = false">
              <i class="fal fa-times"></i>
            </button>
          </div>
        </div>
      </div>
      <nav class="menu-accueil-mobile">
        <a href="#apropos" @click="menuMobile = false">À propos</a>
        <a href="#filieres" @click="menuMobile = false">Formations</a>
        <router-link to="/formalites-inscription" @click="menuMobile = false">Formalités d’inscription</router-link>
        <a href="#qualite" @click="menuMobile = false">Qualité</a>
        <a href="#actualites" @click="menuMobile = false">Actualités</a>
        <a href="#contact" @click="menuMobile = false">Contact</a>
        <router-link to="/inscription-parent" @click="menuMobile = false">Espace parent</router-link>
        <router-link to="/connexion" @click="menuMobile = false">Connexion</router-link>
      </nav>
    </div>
    <div class="offcanvas-overlay" :class="{ 'overlay-open': menuMobile }" @click="menuMobile = false"></div>

    <header>
      <div class="h7_header-top d-none d-md-block">
        <div class="container">
          <div class="row">
            <div class="col-lg-8 col-md-9">
              <ul class="h7_header-top-list">
                <li><a href="tel:+2252720309721"><i class="fa-light fa-phone"></i>(225) 27 20 30 97 21</a></li>
                <li><a href="mailto:epcci@cci.ci"><i class="fa-light fa-envelope"></i>epcci@cci.ci</a></li>
                <li><a href="https://www.epcci.edu.ci/" target="_blank" rel="noopener"><i class="fa-light fa-globe"></i>www.epcci.edu.ci</a></li>
              </ul>
            </div>
            <div class="col-lg-4 col-md-3">
              <div class="h7_header-top-social">
                <ul>
                  <li><span>Rentrée académique 2026-2027</span></li>
                </ul>
              </div>
            </div>
          </div>
        </div>
      </div>
      <div class="h7_header-area header-sticky">
        <div class="container">
          <div class="epcci-nav-bar">
            <div class="h7_header-logo">
              <router-link to="/">
                <img src="/accueil/images/epcci/logo.jpg" alt="EPCCI" class="logo-epcci">
              </router-link>
            </div>
            <nav class="h7_main-menu epcci-nav-liens d-none d-xl-block" aria-label="Navigation principale">
              <ul>
                <li><a href="#apropos">À propos</a></li>
                <li><a href="#filieres">Formations</a></li>
                <li><router-link to="/formalites-inscription">Admissions</router-link></li>
                <li><a href="#qualite">Qualité</a></li>
                <li><a href="#actualites">Actualités</a></li>
                <li><a href="#contact">Contact</a></li>
              </ul>
            </nav>
            <div class="epcci-nav-actions">
              <router-link to="/connexion" class="h7_header-btn theme-btn theme-btn-medium theme-btn-6 d-none d-sm-inline-flex">
                Connexion
              </router-link>
              <button class="header-menu-bar-icon side-toggle border-0 bg-transparent d-xl-none" type="button" aria-label="Menu" @click="menuMobile = true">
                <i class="fa-light fa-bars"></i>
              </button>
            </div>
          </div>
        </div>
      </div>
    </header>

    <main>
      <section class="slider-area fix">
        <div
          class="h7_single-banner bg-default epcci-banner"
          :style="{ backgroundImage: `url(${diapos[diapoActuelle].image})` }"
        >
          <div class="container">
            <div class="row">
              <div class="col-xl-8 col-lg-9 col-md-11">
                <div class="h7_banner-content">
                  <p class="epcci-rentree">{{ diapos[diapoActuelle].surTitre }}</p>
                  <h1 class="h7_banner-content-title">{{ diapos[diapoActuelle].titre }}</h1>
                  <p class="h7_banner-content-text">{{ diapos[diapoActuelle].texte }}</p>
                  <div class="h7_banner-content-btn">
                    <router-link to="/connexion" class="theme-btn theme-btn-7">Accéder à la plateforme <i class="fa-light fa-arrow-right"></i></router-link>
                    <a href="#filieres" class="theme-btn theme-btn-7 theme-btn-7-yellow">Découvrir <i class="fa-light fa-arrow-right"></i></a>
                  </div>
                </div>
              </div>
            </div>
          </div>
          <div class="epcci-diapo-nav d-none d-md-flex">
            <button type="button" aria-label="Précédent" @click="diapoPrecedente"><i class="fa-regular fa-arrow-left"></i></button>
            <button type="button" aria-label="Suivant" @click="diapoSuivante"><i class="fa-regular fa-arrow-right"></i></button>
          </div>
        </div>
      </section>

      <section id="apropos" class="h7_about-area pt-120 pb-50">
        <div class="container">
          <div class="row align-items-center">
            <div class="col-xl-6 col-lg-6">
              <div class="h7_about-wrap mb-50 mr-70">
                <div class="section-area-6 mb-40">
                  <span class="section-subtitle">Présentation</span>
                  <h2 class="section-title mb-15">Bienvenue à l’EPCCI</h2>
                  <p class="section-text">
                    L’École Pratique de la Chambre de Commerce et d’Industrie de Côte d’Ivoire
                    forme des hommes et des femmes pour l’entreprise depuis 1938, dans des filières diverses et variées.
                  </p>
                  <p class="section-text">
                    Nos diplômes en licences et masters sont homologués.
                    Les inscriptions et réinscriptions pour la rentrée académique 2026-2027 sont ouvertes.
                  </p>
                </div>
                <div class="h7_about-content">
                  <div>
                    <span class="section-subtitle">Notre devise : CRAC</span>
                    <ul class="epcci-devise-liste">
                      <li><strong>C</strong>ompétence</li>
                      <li><strong>R</strong>esponsabilité</li>
                      <li><strong>A</strong>utonomie</li>
                      <li><strong>C</strong>réativité</li>
                    </ul>
                  </div>
                </div>
              </div>
            </div>
            <div class="col-xl-6 col-lg-6">
              <div class="h7_about-img w_img mb-50">
                <img src="/accueil/images/epcci/slide-2.jpg" alt="Campus EPCCI">
              </div>
            </div>
          </div>
        </div>
      </section>

      <section id="filieres" class="h7_program-area pb-90 fix">
        <div class="program-text-wrap mb-55">
          <div class="program-text-ticker">
            <h1 class="h7_program-title">Nos offres de formation</h1>
          </div>
        </div>
        <div class="container">
          <p class="text-center mb-40 epcci-intro-formations">
            11 filières de formation diverses et variées — BTS, licences professionnelles et masters.
          </p>
          <div class="h7_program-wrap">
            <div class="row g-0">
              <div class="col-xl-4 col-lg-4">
                <div class="h7_program-item">
                  <h3 class="h7_program-item-title">BTS</h3>
                  <ul class="h7_program-item-list2">
                    <li v-for="f in bts" :key="f">{{ f }}</li>
                  </ul>
                </div>
              </div>
              <div class="col-xl-4 col-lg-4">
                <div class="h7_program-item h7_program-item2">
                  <h3 class="h7_program-item-title">Licences professionnelles</h3>
                  <ul class="h7_program-item-list2">
                    <li v-for="f in licences" :key="f">{{ f }}</li>
                  </ul>
                </div>
              </div>
              <div class="col-xl-4 col-lg-4">
                <div class="h7_program-item">
                  <h3 class="h7_program-item-title">Masters</h3>
                  <ul class="h7_program-item-list2">
                    <li v-for="f in masters" :key="f">{{ f }}</li>
                  </ul>
                  <a href="https://www.epcci.edu.ci/" class="h7_program-item-list" target="_blank" rel="noopener">
                    Voir sur le site de l’école <i class="fa-light fa-arrow-right"></i>
                  </a>
                </div>
              </div>
            </div>
          </div>
        </div>
      </section>

      <section class="h7_apply-area pt-10 pb-95 fix">
        <div class="apply-text-wrap mb-80">
          <div class="apply-text-ticker">
            <h1 class="h7_apply-title">Accès &amp; services</h1>
          </div>
        </div>
        <div class="container">
          <div class="h7_apply-wrap">
            <div class="h7_apply-item">
              <div class="h7_apply-item-number"><span>01</span></div>
              <div class="h7_apply-item-title"><h4>Inscription en ligne</h4></div>
              <div class="h7_apply-item-text">
                <p>L’admission à l’EPCCI se fait sur la base du dépôt des dossiers selon la filière. Les inscriptions 2026-2027 sont ouvertes.</p>
              </div>
              <div class="h7_apply-item-img"><img src="/accueil/images/epcci/inscription.jpg" alt=""></div>
            </div>
            <div class="h7_apply-item">
              <div class="h7_apply-item-number"><span>02</span></div>
              <div class="h7_apply-item-title"><h4>Formations</h4></div>
              <div class="h7_apply-item-text">
                <p>Nous vous offrons 11 filières de formation diverses et variées, du BTS au master.</p>
              </div>
              <div class="h7_apply-item-img"><img src="/accueil/images/epcci/docs.jpg" alt=""></div>
            </div>
            <div class="h7_apply-item">
              <div class="h7_apply-item-number"><span>03</span></div>
              <div class="h7_apply-item-title"><h4>Portail de scolarité</h4></div>
              <div class="h7_apply-item-text">
                <p>Notes publiées, supports de cours et suivi des frais en FCFA pour les étudiants, parents et enseignants.</p>
              </div>
              <div class="h7_apply-item-img"><img src="/accueil/images/epcci/actu-3.jpg" alt=""></div>
            </div>
            <router-link to="/connexion" class="h7_apply-wrap-btn">Se connecter au portail</router-link>
          </div>
        </div>
      </section>

      <section id="qualite" class="epcci-qualite py-5">
        <div class="container">
          <div class="row align-items-center g-4">
            <div class="col-md-3 text-center">
              <img src="/accueil/images/epcci/iso.jpg" alt="ISO 9001" class="epcci-iso">
            </div>
            <div class="col-md-9">
              <h2 class="section-title mb-3">EPCCI, premier établissement certifié ISO 9001 version 2015</h2>
              <div class="row">
                <div class="col-md-4 mb-3">
                  <h5>L’EPCCI s’engage à</h5>
                  <p>Faire de la satisfaction de ses clients la priorité de chaque collaborateur.</p>
                </div>
                <div class="col-md-4 mb-3">
                  <h5>L’EPCCI s’engage à</h5>
                  <p>Améliorer en permanence le taux de satisfaction des entreprises, des parents et des étudiants.</p>
                </div>
                <div class="col-md-4 mb-3">
                  <h5>L’EPCCI s’engage à</h5>
                  <p>Améliorer le taux d’insertion professionnelle de ses apprenants.</p>
                </div>
              </div>
              <a href="https://www.epcci.edu.ci/" class="theme-btn theme-btn-medium theme-btn-6" target="_blank" rel="noopener">Découvrir notre politique qualité</a>
            </div>
          </div>
        </div>
      </section>

      <section class="h6_campus-area fix">
        <div class="h6_campus-wrap">
          <div class="h6_campus-item-1">
            <img src="/accueil/images/epcci/slide-1.jpg" alt="">
            <div class="campus-text-wrap">
              <div class="campus-text-ticker">
                <h1 class="h6_campus-title">Rentrée académique 2026-2027</h1>
              </div>
            </div>
          </div>
          <div class="h6_campus-item-2">
            <img src="/accueil/images/epcci/slide-3.jpg" alt="">
            <h4>Les inscriptions et réinscriptions sont ouvertes</h4>
            <ul>
              <li><a href="https://www.epcci.edu.ci/" target="_blank" rel="noopener">Préinscription en ligne <i class="fa-light fa-arrow-right"></i></a></li>
              <li><router-link to="/formalites-inscription">Formalités d’inscription <i class="fa-light fa-arrow-right"></i></router-link></li>
              <li><router-link to="/inscription-parent">Compte parent du portail <i class="fa-light fa-arrow-right"></i></router-link></li>
            </ul>
          </div>
          <div class="h6_campus-item-3">
            <img src="/accueil/images/epcci/slide-2.jpg" alt="">
            <div class="campus-text-wrap-2">
              <div class="campus-text-ticker-2">
                <h1 class="h6_campus-title">Compétence · Responsabilité · Autonomie · Créativité</h1>
              </div>
            </div>
          </div>
        </div>
      </section>

      <section id="actualites" class="h5_blog-area h6_blog-area pt-110 pb-90">
        <div class="container">
          <div class="row align-items-end mb-30">
            <div class="col-md-9">
              <div class="section-area-6 mb-30">
                <span class="section-subtitle">Actualités</span>
                <h2 class="section-title mb-0">À la une sur epcci.edu.ci</h2>
              </div>
            </div>
            <div class="col-md-3 text-md-end mb-40">
              <a href="https://www.epcci.edu.ci/" class="theme-btn theme-btn-medium theme-btn-6" target="_blank" rel="noopener">Toutes les actualités</a>
            </div>
          </div>
          <div class="row">
            <div v-for="actu in actualites" :key="actu.titre" class="col-xl-4 col-lg-6 col-md-6">
              <a class="epcci-actu-carte mb-30" :href="actu.url" target="_blank" rel="noopener">
                <img :src="actu.image" :alt="actu.titre">
                <div class="epcci-actu-corps">
                  <h5>{{ actu.titre }}</h5>
                  <span>lire plus…</span>
                </div>
              </a>
            </div>
          </div>
        </div>
      </section>

      <div class="h6_cta-area pt-40 pb-120">
        <div class="container">
          <div class="h6_cta-wrapper" style="background-image: url('/accueil/images/epcci/slide-1.jpg'); background-size: cover; background-position: center;">
            <div class="h6_cta-content mb-30 mb-md-0">
              <h2 class="h6_cta-title">Suivez la scolarité sur le portail EPCCI</h2>
            </div>
            <div class="h6_cta-button">
              <router-link to="/connexion" class="h6_cta-btn">Connexion <i class="fa-light fa-arrow-up-right"></i></router-link>
            </div>
          </div>
        </div>
      </div>
    </main>

    <footer id="contact" class="h6_footer-area">
      <div class="footer-top pt-100 pb-40">
        <div class="container">
          <div class="row justify-content-between">
            <div class="col-xl-4 col-lg-4 col-md-6">
              <div class="h6_footer-widget mb-40">
                <img src="/accueil/images/epcci/logo.jpg" alt="EPCCI" class="logo-epcci logo-epcci-footer mb-3">
                <p>
                  École Pratique de la Chambre de Commerce et d’Industrie de Côte d’Ivoire.
                  Ce portail de scolarité complète le site institutionnel
                  <a href="https://www.epcci.edu.ci/" target="_blank" rel="noopener">www.epcci.edu.ci</a>.
                </p>
              </div>
            </div>
            <div class="col-xl-3 col-lg-3 col-md-6">
              <div class="h6_footer-widget mb-40">
                <h4 class="mb-3">Nous contacter</h4>
                <p>(225) 27 20 30 97 21</p>
                <p><a href="mailto:epcci@cci.ci">epcci@cci.ci</a></p>
                <p><a href="https://www.epcci.edu.ci/" target="_blank" rel="noopener">www.epcci.edu.ci</a></p>
              </div>
            </div>
            <div class="col-xl-3 col-lg-3 col-md-6">
              <div class="h6_footer-widget mb-40">
                <h4 class="mb-3">Accès</h4>
                <p><router-link to="/connexion">Connexion au portail</router-link></p>
                <p><router-link to="/inscription-parent">Inscription parent</router-link></p>
                <p><a href="https://www.epcci.edu.ci/" target="_blank" rel="noopener">Site de l’école</a></p>
              </div>
            </div>
          </div>
        </div>
      </div>
    </footer>
  </div>
</template>

<script setup>
import { onMounted, onUnmounted, ref } from 'vue'

const menuMobile = ref(false)
const diapoActuelle = ref(0)
let timerDiapo = null

const diapos = [
  {
    image: '/accueil/images/epcci/slide-1.jpg',
    surTitre: 'Rentrée académique 2026-2027',
    titre: 'Bienvenue à l’EPCCI',
    texte: 'L’École Pratique de la Chambre de Commerce et d’Industrie de Côte d’Ivoire forme des hommes et des femmes pour l’entreprise depuis 1938, dans des filières diverses et variées.',
  },
  {
    image: '/accueil/images/epcci/slide-2.jpg',
    surTitre: 'Inscriptions ouvertes',
    titre: 'Les inscriptions et réinscriptions sont ouvertes',
    texte: 'Préparez la rentrée académique 2026-2027. Consultez les formalités et la préinscription sur le site de l’école.',
  },
  {
    image: '/accueil/images/epcci/slide-3.jpg',
    surTitre: 'Qualité',
    titre: 'Certifiée ISO 9001 version 2015',
    texte: 'Nos diplômes en licences et masters sont homologués. Devise CRAC : Compétence, Responsabilité, Autonomie, Créativité.',
  },
]

const bts = [
  'Assistanat de Direction (AD)',
  'Finances Comptabilité et Gestion d’Entreprises (FCGE)',
  'Gestion Commerciale (GC)',
  'Informatique – Développeur d’Application (IDA)',
  'Tourisme et Hôtellerie',
  'Logistique',
]

const licences = [
  'Assistanat de Direction',
  'Audit Comptabilité Contrôle',
  'Management des Ventes et Marketing Appliqué',
  'Réseau Informatique et Génie Logiciel',
]

const masters = [
  'Audit Comptabilité Contrôle',
  'Management des Ventes et Marketing Appliqué',
  'Réseau Informatique et Génie Logiciel',
]

const actualites = [
  {
    titre: 'Lancement officiel du réseau des Alumni de l’EPCCI',
    image: '/accueil/images/epcci/actu-1.jpg',
    url: 'https://www.epcci.edu.ci/',
  },
  {
    titre: 'Échange avec la présidente de l’Ordre des experts-comptables',
    image: '/accueil/images/epcci/actu-2.jpg',
    url: 'https://www.epcci.edu.ci/',
  },
  {
    titre: 'Vie associative : les étudiants lancent leurs activités',
    image: '/accueil/images/epcci/actu-3.jpg',
    url: 'https://www.epcci.edu.ci/',
  },
]

function diapoSuivante() {
  diapoActuelle.value = (diapoActuelle.value + 1) % diapos.length
}
function diapoPrecedente() {
  diapoActuelle.value = (diapoActuelle.value - 1 + diapos.length) % diapos.length
}

const feuilles = [
  '/accueil/css/fontawesome-all.min.css',
  '/accueil/css/main.css',
  '/accueil/css/epcci-accueil.css',
]
const liensCrees = []

function chargerStyles() {
  document.body.classList.add('page-accueil-eduan')
  feuilles.forEach((href) => {
    if (document.querySelector(`link[data-accueil="${href}"]`)) return
    const link = document.createElement('link')
    link.rel = 'stylesheet'
    link.href = href
    link.dataset.accueil = href
    document.head.appendChild(link)
    liensCrees.push(link)
  })
}

function retirerStyles() {
  document.body.classList.remove('page-accueil-eduan')
  liensCrees.forEach((link) => link.remove())
  liensCrees.length = 0
  document.querySelectorAll('link[data-accueil]').forEach((link) => link.remove())
}

onMounted(() => {
  chargerStyles()
  timerDiapo = window.setInterval(diapoSuivante, 7000)
})
onUnmounted(() => {
  retirerStyles()
  if (timerDiapo) window.clearInterval(timerDiapo)
})
</script>
