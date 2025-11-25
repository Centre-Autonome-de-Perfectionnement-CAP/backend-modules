import{j as e,g as C,r as a}from"./index-CqzX4VGp.js";import{P as A}from"./PageTitle-q4R9Zymc.js";import{S as b}from"./SectionTitle-D7qsLyQX.js";import{g as k,f as P}from"./administrationService-DFE1yZpz.js";import{a as S}from"./api-DfxUohIt.js";const D=async t=>(await S.get("/api/stockage/documents")).data,q=()=>e.jsx("div",{className:"container","data-aos":"fade-up","data-aos-delay":"100",children:e.jsxs("div",{className:"row align-items-center",children:[e.jsx("div",{className:"col-lg-6","data-aos":"fade-up","data-aos-delay":"200",children:e.jsx("img",{src:C("assets/img/education/cap-bat.png"),alt:"À propos du CAP",className:"img-fluid rounded-4"})}),e.jsx("div",{className:"col-lg-6","data-aos":"fade-up","data-aos-delay":"300",children:e.jsxs("div",{className:"about-content",children:[e.jsx("span",{className:"subtitle",children:"À propos du CAP"}),e.jsx("h2",{children:"Former les Leaders de Demain par une Éducation d'Excellence"}),e.jsx("p",{children:"Le Centre Autonome de Perfectionnement (CAP) est une institution d'enseignement supérieur de référence, engagée à fournir une formation académique et professionnelle de qualité. Nous préparons nos étudiants à relever les défis du monde moderne avec compétence et confiance."}),e.jsxs("div",{className:"stats-row",children:[e.jsxs("div",{className:"stats-item",children:[e.jsx("span",{className:"count",children:"15"}),e.jsx("p",{children:"Années d'Expérience"})]}),e.jsxs("div",{className:"stats-item",children:[e.jsx("span",{className:"count",children:"200+"}),e.jsx("p",{children:"Enseignants Experts"})]}),e.jsxs("div",{className:"stats-item",children:[e.jsx("span",{className:"count",children:"50k+"}),e.jsx("p",{children:"Étudiants Formés"})]})]})]})})]})}),F=({administrateur:t,delay:u})=>e.jsx("div",{className:"col-xl-3 col-lg-4 col-md-6","data-aos":"fade-up","data-aos-delay":u,children:e.jsxs("div",{className:"administration-card card border-0 shadow-sm h-100",children:[e.jsxs("div",{className:"card-image position-relative",children:[e.jsx("img",{src:t.image,className:"card-img-top",alt:t.nom,style:{height:"280px",objectFit:"cover"}}),e.jsx("div",{className:"position-absolute top-0 start-0 m-3",children:e.jsx("div",{className:"bg-primary text-white rounded-circle d-flex align-items-center justify-content-center",style:{width:"40px",height:"40px",fontSize:"0.9rem",fontWeight:"bold"},children:t.ordre})})]}),e.jsxs("div",{className:"card-body d-flex flex-column",children:[e.jsxs("div",{children:[e.jsx("h5",{className:"card-title fw-bold text-dark",children:t.nom}),e.jsx("p",{className:"text-primary fw-semibold",children:t.poste})]}),e.jsx("div",{className:"",children:e.jsxs("div",{className:"contact-info",children:[t.email&&e.jsxs("div",{className:"d-flex align-items-center mb-2",children:[e.jsx("i",{className:"bi bi-envelope text-muted me-2"}),e.jsx("small",{className:"text-muted",children:t.email})]}),t.telephone&&e.jsxs("div",{className:"d-flex align-items-center",children:[e.jsx("i",{className:"bi bi-telephone text-muted me-2"}),e.jsx("small",{className:"text-muted",children:t.telephone})]})]})})]})]})}),T=()=>{const[t,u]=a.useState([]),[c,x]=a.useState(!0),[o,g]=a.useState(null);return a.useEffect(()=>{(async()=>{try{x(!0);const n=(await k()).map(P),h={"Chef du CAP":1,"Chef de Division":2,Comptable:3,Secrétaire:4};n.sort((l,p)=>{const m=h[l.poste]||999,j=h[p.poste]||999;return m-j}),u(n)}catch(d){console.error("Erreur chargement administration:",d),g("Impossible de charger les membres de l'administration")}finally{x(!1)}})()},[]),e.jsx("section",{id:"administration",className:"administration section bg-light",children:e.jsxs("div",{className:"container",children:[e.jsx(b,{title:"Notre Équipe Administrative",subtitle:"Découvrez les membres de l'administration qui œuvrent au quotidien pour l'excellence du CAP"}),c&&e.jsxs("div",{className:"text-center py-5",children:[e.jsx("div",{className:"spinner-border text-primary",role:"status",children:e.jsx("span",{className:"visually-hidden",children:"Chargement..."})}),e.jsx("p",{className:"mt-3 text-muted",children:"Chargement de l'équipe administrative..."})]}),o&&e.jsx("div",{className:"alert alert-warning text-center",role:"alert",children:o}),!c&&!o&&e.jsx("div",{className:"row g-4 justify-content-start",children:t.map((r,d)=>e.jsx(F,{administrateur:r,delay:200+d*100},r.id))}),e.jsx("style",{children:`
          .administration-card {
            transition: transform 0.2s ease-in-out;
            border-radius: 12px;
          }
          
          .administration-card:hover {
            transform: translateY(-5px);
          }
          
          .card-image {
            border-radius: 12px 12px 0 0;
            overflow: hidden;
          }
          
          .contact-info {
            border-top: 1px solid #e9ecef;
            padding-top: 1rem;
          }
          
          @media (max-width: 768px) {
            .administration-card {
              margin-bottom: 1.5rem;
            }
          }
        `})]})})},L=()=>{const[t,u]=a.useState([]),[c,x]=a.useState(!0),[o,g]=a.useState(null),[r,d]=a.useState("tous"),[n,h]=a.useState("tous"),[l,p]=a.useState("");a.useEffect(()=>{(async()=>{try{x(!0),console.log("🔄 Chargement des documents...");const i=await D();console.log("✅ Documents reçus:",i.length,i),u(i)}catch(i){console.error("❌ Erreur chargement documents:",i),g("Impossible de charger les documents")}finally{x(!1)}})()},[]);const m=a.useMemo(()=>!t||t.length===0?[]:t.filter(s=>{const i=r==="tous"||s.categorie===r,w=n==="tous"||s.type===n,E=l===""||s.titre.toLowerCase().includes(l.toLowerCase())||s.description.toLowerCase().includes(l.toLowerCase());return i&&w&&E}),[r,n,l]),j=s=>{switch(s){case"pdf":return"bi-file-earmark-pdf-fill text-danger";case"doc":return"bi-file-earmark-word-fill text-primary";case"xls":return"bi-file-earmark-excel-fill text-success";case"ppt":return"bi-file-earmark-ppt-fill text-warning";default:return"bi-file-earmark-text-fill text-secondary"}},N=s=>{switch(s){case"pedagogique":return"bg-primary";case"administratif":return"bg-success";case"legal":return"bg-warning text-dark";case"organisation":return"bg-info";default:return"bg-secondary"}},v=s=>{switch(s){case"pedagogique":return"Pédagogique";case"administratif":return"Administratif";case"legal":return"Juridique";case"organisation":return"Organisation";default:return s}},y=s=>new Date(s).toLocaleDateString("fr-FR",{day:"numeric",month:"long",year:"numeric"}),f=()=>{d("tous"),h("tous"),p("")};return e.jsxs("section",{id:"documents-utiles",className:"documents-utiles section",children:[e.jsxs("div",{className:"container",children:[e.jsx(b,{title:"Documents Utiles",subtitle:"Téléchargez tous les documents officiels et ressources importantes du CAP"}),e.jsxs("div",{className:"filtres-container mb-5","data-aos":"fade-up","data-aos-delay":"100",children:[e.jsxs("div",{className:"row g-3",children:[e.jsx("div",{className:"col-lg-4",children:e.jsxs("div",{className:"search-box position-relative",children:[e.jsx("i",{className:"bi bi-search position-absolute top-50 start-3 translate-middle-y text-muted"}),e.jsx("input",{type:"text",className:"form-control ps-5",placeholder:"Rechercher un document...",value:l,onChange:s=>p(s.target.value)})]})}),e.jsx("div",{className:"col-lg-4",children:e.jsxs("select",{className:"form-select",value:r,onChange:s=>d(s.target.value),children:[e.jsx("option",{value:"tous",children:"Toutes les catégories"}),e.jsx("option",{value:"pedagogique",children:"Pédagogique"}),e.jsx("option",{value:"administratif",children:"Administratif"}),e.jsx("option",{value:"legal",children:"Juridique"}),e.jsx("option",{value:"organisation",children:"Organisation"})]})}),e.jsx("div",{className:"col-lg-3",children:e.jsxs("select",{className:"form-select",value:n,onChange:s=>h(s.target.value),children:[e.jsx("option",{value:"tous",children:"Tous les types"}),e.jsx("option",{value:"pdf",children:"PDF"}),e.jsx("option",{value:"doc",children:"Word"}),e.jsx("option",{value:"xls",children:"Excel"}),e.jsx("option",{value:"ppt",children:"PowerPoint"})]})}),e.jsx("div",{className:"col-lg-1",children:e.jsx("button",{className:"btn btn-outline-secondary w-100",onClick:f,title:"Réinitialiser les filtres",children:e.jsx("i",{className:"bi bi-arrow-clockwise"})})})]}),e.jsx("div",{className:"row mt-3",children:e.jsx("div",{className:"col-12",children:e.jsxs("div",{className:"d-flex justify-content-between align-items-center",children:[e.jsxs("small",{className:"text-muted",children:[m.length," document",m.length>1?"s":""," trouvé",m.length>1?"s":""]}),(r!=="tous"||n!=="tous"||l)&&e.jsx("button",{className:"btn btn-link btn-sm text-decoration-none p-0",onClick:f,children:e.jsx("small",{children:"Effacer les filtres"})})]})})})]}),c&&e.jsxs("div",{className:"text-center py-5",children:[e.jsx("div",{className:"spinner-border text-primary",role:"status",children:e.jsx("span",{className:"visually-hidden",children:"Chargement..."})}),e.jsx("p",{className:"mt-3 text-muted",children:"Chargement des documents..."})]}),o&&!c&&e.jsxs("div",{className:"alert alert-warning text-center",role:"alert",children:[e.jsx("i",{className:"bi bi-exclamation-triangle me-2"}),o]}),!c&&!o&&e.jsx("div",{className:"row",children:e.jsx("div",{className:"col-12",children:m.length>0?e.jsx("div",{className:"documents-grid",children:m.map((s,i)=>e.jsx("div",{className:"document-card card border-0 shadow-sm h-100","data-aos":"fade-up","data-aos-delay":100+i*50,children:e.jsxs("div",{className:"card-body p-4",children:[e.jsxs("div",{className:"d-flex align-items-start mb-3",children:[e.jsx("div",{className:"document-icon me-3",children:e.jsx("i",{className:`bi ${j(s.type)} fs-2`})}),e.jsxs("div",{className:"flex-grow-1",children:[e.jsxs("div",{className:"d-flex justify-content-between align-items-start mb-2",children:[e.jsx("h5",{className:"card-title fw-bold text-dark mb-1",children:s.titre}),e.jsx("span",{className:`badge ${N(s.categorie)}`,children:v(s.categorie)})]}),e.jsx("p",{className:"card-text text-muted small mb-3",children:s.description})]})]}),e.jsxs("div",{className:"document-meta d-flex justify-content-between align-items-center",children:[e.jsxs("div",{className:"document-info",children:[e.jsxs("small",{className:"text-muted",children:[e.jsx("i",{className:"bi bi-calendar3 me-1"}),"Publié le ",y(s.datePublication)]}),s.taille&&e.jsxs("small",{className:"text-muted ms-3",children:[e.jsx("i",{className:"bi bi-hdd me-1"}),s.taille]})]}),e.jsxs("a",{href:s.lien,className:"btn btn-primary btn-sm",target:"_blank",rel:"noopener noreferrer",download:!0,children:[e.jsx("i",{className:"bi bi-download me-1"}),"Télécharger"]})]})]})},s.id))}):e.jsx("div",{className:"text-center py-5","data-aos":"fade-up",children:e.jsxs("div",{className:"empty-state",children:[e.jsx("i",{className:"bi bi-file-earmark-x text-muted fs-1 mb-3"}),e.jsx("h5",{className:"text-muted",children:"Aucun document trouvé"}),e.jsx("p",{className:"text-muted mb-4",children:"Aucun document ne correspond à vos critères de recherche."}),e.jsxs("button",{className:"btn btn-primary",onClick:f,children:[e.jsx("i",{className:"bi bi-arrow-clockwise me-2"}),"Réinitialiser les filtres"]})]})})})}),e.jsx("div",{className:"text-center mt-5",children:e.jsx("div",{className:"alert alert-info border-0",children:e.jsxs("div",{className:"d-flex align-items-center",children:[e.jsx("i",{className:"bi bi-info-circle-fill text-primary fs-4 me-3"}),e.jsxs("div",{className:"text-start",children:[e.jsx("h6",{className:"alert-heading mb-1",children:"Besoin d'autres documents ?"}),e.jsxs("p",{className:"mb-0 small",children:["Si vous ne trouvez pas le document recherché, n'hésitez pas à",e.jsx("a",{href:"/contact",className:"text-decoration-none fw-bold",children:" nous contacter"}),"."]})]})]})})})]}),e.jsx("style",{children:`
        .documents-grid {
          display: grid;
          grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
          gap: 1.5rem;
        }
        
        .document-card {
          transition: transform 0.2s ease-in-out, box-shadow 0.2s ease-in-out;
          border-radius: 12px;
        }
        
        .document-card:hover {
          transform: translateY(-3px);
          box-shadow: 0 8px 25px rgba(0,0,0,0.1) !important;
        }
        
        .document-icon {
          flex-shrink: 0;
        }
        
        .document-meta {
          border-top: 1px solid #e9ecef;
          padding-top: 1rem;
        }
        
        .badge {
          font-size: 0.7rem;
          padding: 4px 8px;
        }
        
        .btn-primary {
          background: linear-gradient(135deg, #316660, #316660);
          border: none;
          border-radius: 6px;
          font-weight: 500;
        }
        
        .search-box .form-control {
          padding-left: 2.5rem;
        }
        
        .empty-state {
          max-width: 400px;
          margin: 0 auto;
        }
        
        @media (max-width: 768px) {
          .documents-grid {
            grid-template-columns: 1fr;
          }
          
          .document-card .card-body {
            padding: 1.5rem;
          }
          
          .filtres-container .row > div {
            margin-bottom: 1rem;
          }
        }
        
        @media (max-width: 576px) {
          .documents-grid {
            grid-template-columns: 1fr;
          }
          
          .document-meta {
            flex-direction: column;
            align-items: flex-start;
            gap: 1rem;
          }
          
          .document-meta .btn {
            align-self: stretch;
          }
        }
      `})]})},J=()=>e.jsxs(e.Fragment,{children:[e.jsx(A,{title:"À propos",breadcrumbs:[{label:"Accueil",path:"/"},{label:"À propos"}]}),e.jsxs("section",{id:"about",className:"about section",children:[e.jsx(q,{}),e.jsx(T,{}),e.jsx(L,{})]})]});export{J as default};
