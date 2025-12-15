import{r as n,j as e,L as g,g as v}from"./index-DRxf0RnU.js";import{g as y,a as w}from"./inscriptionService-Bfio8Ytz.js";import{S as b}from"./SectionTitle-61LDn3Sa.js";import{a as C}from"./api-DfxUohIt.js";const k=()=>{const[a,r]=n.useState(null),[m,h]=n.useState({}),[t,l]=n.useState(!0),[f,N]=n.useState(null);return n.useEffect(()=>{(async()=>{try{l(!0);const i=await y();r(i)}catch(i){console.error("Erreur chargement deadline:",i),N("Impossible de charger les informations d'inscription")}finally{l(!1)}})()},[]),n.useEffect(()=>{if(!a||a.status==="closed"||a.periods.length===0)return;const x=d=>{const p=new Date(d).getTime(),j=new Date().getTime(),u=p-j;return u>0?{jours:Math.floor(u/(1e3*60*60*24)),heures:Math.floor(u/(1e3*60*60)%24),minutes:Math.floor(u/1e3/60%60),secondes:Math.floor(u/1e3%60)}:{jours:0,heures:0,minutes:0,secondes:0}},i=()=>{const d={};a.periods.forEach((p,j)=>{d[j]=x(p.deadline)}),h(d)};i();const o=setInterval(i,1e3);return()=>clearInterval(o)},[a]),e.jsxs("section",{id:"courses-hero",className:"courses-hero section light-background",children:[e.jsx("div",{className:"hero-content",children:e.jsx("div",{className:"container",children:e.jsxs("div",{className:"row align-items-center",children:[e.jsxs("div",{className:"col-lg justify-content-center","data-aos":"fade-up","data-aos-delay":"100",children:[a?.status==="open"&&a.periods.length>0&&e.jsxs("div",{className:"mb-4",children:[e.jsx("div",{className:"d-flex flex gap-2",children:a.periods.slice(0,3).map((x,i)=>{const o=m[i]||{jours:0,heures:0},d=x.filieres.length;return e.jsxs("div",{className:"countdown-card",style:{backgroundColor:i===0?"#EBF2FD":"#F5F9FF",color:"#555",padding:"12px 15px",textAlign:"center",fontWeight:i===0?"bold":"normal",position:"relative",borderRadius:"8px",border:i===0?"2px solid #0066cc":"1px solid #ddd",width:"100%",boxShadow:"0 2px 4px rgba(0,0,0,0.05)"},children:[e.jsxs("p",{style:{margin:0,fontSize:"0.85em"},children:["⏰ ",i===0?e.jsx("strong",{children:"Période en cours"}):"Période à venir"]}),e.jsxs("p",{style:{margin:"5px 0",fontSize:"0.9em",color:"#333",fontWeight:600},children:[d," filière",d>1?"s":""," disponible",d>1?"s":""]}),e.jsx("p",{style:{margin:"5px 0 0 0",fontSize:"0.8em",color:"#666"},children:o.jours>0?e.jsxs(e.Fragment,{children:["Fin dans :",e.jsxs("span",{style:{marginLeft:"5px",fontSize:"1em",color:i===0?"#0066cc":"#555",fontWeight:600},children:[o.jours,"j ",o.heures,"h"]})]}):e.jsxs(e.Fragment,{children:["Commence dans :",e.jsx("span",{style:{marginLeft:"5px",fontSize:"1em",color:"#555",fontWeight:600},children:"Bientôt"})]})})]},i)})}),e.jsx("div",{className:"text-center mt-3",children:e.jsxs("a",{href:"#featured-courses",className:"btn btn-outline-primary btn-sm",style:{borderRadius:"20px",padding:"8px 24px"},children:[e.jsx("i",{className:"bi bi-eye me-2"}),"Voir toutes les filières"]})})]}),e.jsxs("div",{className:"hero-text",children:[e.jsx("h1",{children:"Transformez Votre Avenir avec le Centre Autonome de Perfectionnement"}),e.jsx("p",{children:"Découvrez nos programmes de formation d'excellence en Licence, Master et Ingénierie. Apprenez à votre rythme, développez des compétences recherchées et progressez dans votre carrière avec le CAP."}),e.jsxs("div",{className:"hero-stats",children:[e.jsxs("div",{className:"stat-item",children:[e.jsx("span",{className:"number purecounter","data-purecounter-start":"0","data-purecounter-end":"5000","data-purecounter-duration":"2"}),e.jsx("span",{className:"label",children:"Étudiants Inscrits"})]}),e.jsxs("div",{className:"stat-item",children:[e.jsx("span",{className:"number purecounter","data-purecounter-start":"0","data-purecounter-end":"50","data-purecounter-duration":"2"}),e.jsx("span",{className:"label",children:"Programmes"})]}),e.jsxs("div",{className:"stat-item",children:[e.jsx("span",{className:"number purecounter","data-purecounter-start":"0","data-purecounter-end":"95","data-purecounter-duration":"2"}),e.jsx("span",{className:"label",children:"Taux de Réussite %"})]})]}),e.jsxs("div",{className:"hero-buttons",children:[e.jsx(g,{to:"/enroll",className:"btn btn-primary",children:"Nos Formations"}),e.jsx(g,{to:"/about",className:"btn btn-outline",children:"En savoir plus"})]}),e.jsxs("div",{className:"hero-features",children:[e.jsxs("div",{className:"feature",children:[e.jsx("i",{className:"bi bi-shield-check"}),e.jsx("span",{children:"Diplômes Certifiés"})]}),e.jsxs("div",{className:"feature",children:[e.jsx("i",{className:"bi bi-clock"}),e.jsx("span",{children:"Formation Continue"})]}),e.jsxs("div",{className:"feature",children:[e.jsx("i",{className:"bi bi-people"}),e.jsx("span",{children:"Enseignants Qualifiés"})]})]})]})]}),e.jsx("div",{className:"col-lg-6","data-aos":"fade-up","data-aos-delay":"200",children:e.jsxs("div",{className:"hero-image",children:[e.jsx("div",{className:"main-image",children:e.jsx("img",{src:v("assets/img/education/cap-bat.png"),alt:"Formation en ligne CAP",className:"img-fluid"})}),e.jsxs("div",{className:"floating-cards",children:[e.jsxs("div",{className:"course-card","data-aos":"fade-up","data-aos-delay":"300",children:[e.jsx("div",{className:"card-icon",children:e.jsx("i",{className:"bi bi-mortarboard"})}),e.jsxs("div",{className:"card-content",children:[e.jsx("h6",{children:"Licence"}),e.jsx("span",{children:"2,450 Étudiants"})]})]}),e.jsxs("div",{className:"course-card","data-aos":"fade-up","data-aos-delay":"400",children:[e.jsx("div",{className:"card-icon",children:e.jsx("i",{className:"bi bi-award"})}),e.jsxs("div",{className:"card-content",children:[e.jsx("h6",{children:"Master"}),e.jsx("span",{children:"1,890 Étudiants"})]})]}),e.jsxs("div",{className:"course-card","data-aos":"fade-up","data-aos-delay":"500",children:[e.jsx("div",{className:"card-icon",children:e.jsx("i",{className:"bi bi-gear"})}),e.jsxs("div",{className:"card-content",children:[e.jsx("h6",{children:"Ingénierie"}),e.jsx("span",{children:"3,200 Étudiants"})]})]})]})]})})]})})}),e.jsx("div",{className:"hero-background",children:e.jsxs("div",{className:"bg-shapes",children:[e.jsx("div",{className:"shape shape-1"}),e.jsx("div",{className:"shape shape-2"}),e.jsx("div",{className:"shape shape-3"})]})})]})},z=()=>e.jsxs("section",{id:"presentation",className:"presentation section bg-light",children:[e.jsx(b,{title:"Bienvenue au Centre Autonome de Perfectionnement",subtitle:"Une institution d'excellence dédiée à votre développement académique et professionnel"}),e.jsx("div",{className:"container","data-aos":"fade-up","data-aos-delay":"100",children:e.jsxs("div",{className:"row gy-5 align-items-center",children:[e.jsx("div",{className:"col-lg-6","data-aos":"fade-right","data-aos-delay":"200",children:e.jsxs("div",{className:"mission-content",children:[e.jsx("div",{className:"section-header mb-4",children:e.jsx("h3",{className:"fw-bold text-dark mb-3",children:"Notre Mission"})}),e.jsxs("div",{className:"mission-text",children:[e.jsxs("p",{className:"lead text-muted mb-4",children:["Le ",e.jsx("strong",{children:"Centre Autonome de Perfectionnement (CAP)"})," est une institution d'enseignement supérieur de référence, engagée dans la formation d'excellence et le développement de compétences adaptées aux besoins du marché du travail moderne."]}),e.jsx("p",{className:"text-dark mb-4",children:"Nous offrons des programmes de formation dans divers domaines, allant de la Licence au Master en passant par des formations spécialisées en Ingénierie. Notre approche pédagogique innovante combine théorie et pratique pour garantir l'employabilité de nos diplômés."}),e.jsxs("div",{className:"features-grid",children:[e.jsxs("div",{className:"feature-item",children:[e.jsx("div",{className:"feature-icon",children:e.jsx("i",{className:"bi bi-award text-primary"})}),e.jsxs("div",{className:"feature-text",children:[e.jsx("h6",{children:"Programmes accrédités"}),e.jsx("p",{className:"mb-0",children:"Formations reconnues par l'État"})]})]}),e.jsxs("div",{className:"feature-item",children:[e.jsx("div",{className:"feature-icon",children:e.jsx("i",{className:"bi bi-person-check text-primary"})}),e.jsxs("div",{className:"feature-text",children:[e.jsx("h6",{children:"Enseignants experts"}),e.jsx("p",{className:"mb-0",children:"Corps professoral qualifié"})]})]}),e.jsxs("div",{className:"feature-item",children:[e.jsx("div",{className:"feature-icon",children:e.jsx("i",{className:"bi bi-building text-primary"})}),e.jsxs("div",{className:"feature-text",children:[e.jsx("h6",{children:"Infrastructure moderne"}),e.jsx("p",{className:"mb-0",children:"Campus équipé et connecté"})]})]}),e.jsxs("div",{className:"feature-item",children:[e.jsx("div",{className:"feature-icon",children:e.jsx("i",{className:"bi bi-graph-up text-primary"})}),e.jsxs("div",{className:"feature-text",children:[e.jsx("h6",{children:"Accompagnement"}),e.jsx("p",{className:"mb-0",children:"Suivi personnalisé des étudiants"})]})]})]})]})]})}),e.jsx("div",{className:"col-lg-6","data-aos":"fade-left","data-aos-delay":"300",children:e.jsxs("div",{className:"values-content",children:[e.jsx("div",{className:"section-header mb-4",children:e.jsx("h3",{className:"fw-bold text-dark mb-3",children:"Nos Valeurs Fondamentales"})}),e.jsxs("div",{className:"values-cards",children:[e.jsx("div",{className:"value-card card border-0 shadow-sm mb-4",children:e.jsx("div",{className:"card-body p-4",children:e.jsxs("div",{className:"d-flex align-items-start",children:[e.jsx("div",{className:"value-icon bg-primary bg-opacity-10 rounded-circle p-3 me-4",children:e.jsx("i",{className:"bi bi-trophy-fill text-primary fs-4"})}),e.jsxs("div",{className:"value-text flex-grow-1",children:[e.jsx("h5",{className:"fw-bold text-dark mb-2",children:"Excellence Académique"}),e.jsx("p",{className:"text-muted mb-0",children:"Nous nous engageons à fournir une éducation de qualité supérieure qui répond aux standards internationaux et prépare nos étudiants à exceller dans leur domaine."})]})]})})}),e.jsx("div",{className:"value-card card border-0 shadow-sm mb-4",children:e.jsx("div",{className:"card-body p-4",children:e.jsxs("div",{className:"d-flex align-items-start",children:[e.jsx("div",{className:"value-icon bg-warning bg-opacity-10 rounded-circle p-3 me-4",children:e.jsx("i",{className:"bi bi-lightbulb-fill text-warning fs-4"})}),e.jsxs("div",{className:"value-text flex-grow-1",children:[e.jsx("h5",{className:"fw-bold text-dark mb-2",children:"Innovation Pédagogique"}),e.jsx("p",{className:"text-muted mb-0",children:"Nous adoptons des méthodes d'enseignement modernes et encourageons la créativité, l'innovation et l'esprit d'entrepreneuriat chez nos étudiants."})]})]})})}),e.jsx("div",{className:"value-card card border-0 shadow-sm mb-4",children:e.jsx("div",{className:"card-body p-4",children:e.jsxs("div",{className:"d-flex align-items-start",children:[e.jsx("div",{className:"value-icon bg-info bg-opacity-10 rounded-circle p-3 me-4",children:e.jsx("i",{className:"bi bi-people-fill text-info fs-4"})}),e.jsxs("div",{className:"value-text flex-grow-1",children:[e.jsx("h5",{className:"fw-bold text-dark mb-2",children:"Communauté Inclusive"}),e.jsx("p",{className:"text-muted mb-0",children:"Nous cultivons un environnement respectueux, diversifié et inclusif où chaque étudiant peut s'épanouir et développer son plein potentiel."})]})]})})})]}),e.jsx("div",{className:"text-center mt-4",children:e.jsxs(g,{to:"/about",className:"btn btn-primary btn-lg px-4 py-2",children:[e.jsx("i",{className:"bi bi-info-circle me-2"}),"Découvrir le CAP"]})})]})})]})}),e.jsx("style",{children:`
        .presentation {
          position: relative;
          overflow: hidden;
        }
        
        .icon-wrapper {
          width: 70px;
          height: 70px;
          border-radius: 20px;
          display: flex;
          align-items: center;
          justify-content: center;
          font-size: 1.8rem;
        }
        
        .mission-content .section-header,
        .values-content .section-header {
          text-align: center;
        }
        
        .features-grid {
          display: grid;
          grid-template-columns: 1fr 1fr;
          gap: 1.5rem;
          margin-top: 2rem;
        }
        
        .feature-item {
          display: flex;
          align-items: flex-start;
          gap: 1rem;
        }
        
        .feature-icon {
          font-size: 1.5rem;
          margin-top: 0.25rem;
          flex-shrink: 0;
        }
        
        .feature-text h6 {
          font-weight: 600;
          color: #2c3e50;
          margin-bottom: 0.25rem;
        }
        
        .feature-text p {
          font-size: 0.9rem;
          color: #6c757d;
          line-height: 1.4;
        }
        
        .value-card {
          transition: transform 0.3s ease, box-shadow 0.3s ease;
          border-radius: 15px;
        }
        
        .value-card:hover {
          transform: translateY(-5px);
          box-shadow: 0 10px 30px rgba(0,0,0,0.1) !important;
        }
        
        .value-icon {
          transition: transform 0.3s ease;
          flex-shrink: 0;
        }
        
        .value-card:hover .value-icon {
          transform: scale(1.1);
        }
        
        .value-text h5 {
          font-size: 1.1rem;
          margin-bottom: 0.75rem;
        }
        
        .value-text p {
          font-size: 0.95rem;
          line-height: 1.6;
        }
        
        .btn-primary {
          background: linear-gradient(135deg, #316660, #316660);
          border: none;
          border-radius: 50px;
          padding: 12px 30px;
          font-weight: 500;
          transition: all 0.3s ease;
        }
        
        .btn-primary:hover {
          transform: translateY(-2px);
          box-shadow: 0 8px 20px rgba(13, 110, 253, 0.3);
        }
        
        @media (max-width: 768px) {
          .features-grid {
            grid-template-columns: 1fr;
            gap: 1rem;
          }
          
          .icon-wrapper {
            width: 60px;
            height: 60px;
            font-size: 1.5rem;
          }
          
          .value-card .card-body {
            padding: 1.5rem;
          }
        }
      `})]}),F={async getImportantInformations(){return(await C.get("/api/rh/important-informations")).data}},I=()=>{const[a,r]=n.useState([]),[m,h]=n.useState(!0);return n.useEffect(()=>{(async()=>{try{const l=await F.getImportantInformations();r(l)}catch(l){console.error("Erreur chargement informations:",l)}finally{h(!1)}})()},[]),m?e.jsx("section",{id:"informations",className:"informations section",children:e.jsx("div",{className:"container text-center py-5",children:e.jsx("div",{className:"spinner-border text-primary",role:"status",children:e.jsx("span",{className:"visually-hidden",children:"Chargement..."})})})}):e.jsxs("section",{id:"informations",className:"informations section",children:[e.jsxs("div",{className:"container","data-aos":"fade-up",children:[e.jsx(b,{title:"Informations Importantes",subtitle:"Restez informés des dernières actualités et opportunités au CAP"}),e.jsx("div",{className:"row gy-4 justify-content-center",children:a.length>0?a.map((t,l)=>e.jsx("div",{className:"col-lg-3 col-md-6","data-aos":"fade-up","data-aos-delay":100+l*100,children:e.jsx("div",{className:"card h-100 shadow-sm border-0",children:e.jsxs("div",{className:"card-body text-center p-4",children:[e.jsx("div",{className:`info-icon bg-${t.color} bg-opacity-10 rounded-circle d-inline-flex align-items-center justify-content-center mb-3`,style:{width:"80px",height:"80px"},children:e.jsx("i",{className:`bi ${t.icon} text-${t.color}`,style:{fontSize:"2rem"}})}),e.jsx("h5",{className:"card-title mb-3",children:t.title}),e.jsx("p",{className:"card-text text-muted mb-4",children:t.description}),t.link&&e.jsxs("a",{href:t.link,className:`btn btn-${t.color} btn-sm px-4 py-2`,children:["En savoir plus ",e.jsx("i",{className:"bi bi-arrow-right ms-1"})]}),t.file&&e.jsxs("a",{href:t.file.url,target:"_blank",rel:"noopener noreferrer",className:`btn btn-${t.color} btn-sm px-4 py-2`,children:[e.jsx("i",{className:"bi bi-file-pdf me-1"})," Télécharger"]})]})})},t.id)):e.jsx("div",{className:"col-12 text-center py-5",children:e.jsx("p",{className:"text-muted",children:"Aucune information disponible pour le moment."})})})]}),e.jsx("style",{children:`
        .informations {
          background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
        }

        .info-icon {
          transition: transform 0.3s ease;
        }

        .card:hover .info-icon {
          transform: scale(1.1);
        }

        .stat-card {
          transition: transform 0.3s ease;
        }

        .stat-card:hover {
          transform: translateY(-5px);
        }

        .stat-number {
          font-size: 2.5rem;
        }

        .stat-label {
          font-weight: 500;
          color: #6c757d;
        }

        @media (max-width: 768px) {
          .stat-number {
            font-size: 2rem;
          }
        }
      `})]})},S=()=>{const a=[{id:1,name:"Licence",icon:"bi-mortarboard",courseCount:15,className:"category-tech",description:"Programmes de premier cycle universitaire dans divers domaines"},{id:2,name:"Master",icon:"bi-award",courseCount:12,className:"category-business",description:"Programmes de deuxième cycle pour approfondir vos compétences"},{id:3,name:"Ingénierie",icon:"bi-gear",courseCount:8,className:"category-design",description:"Formations spécialisées en ingénierie et technologie"}];return e.jsxs("section",{id:"course-categories",className:"course-categories section",children:[e.jsx(b,{title:"Offre de Formations",subtitle:"Découvrez nos programmes de formation adaptés à vos ambitions académiques et professionnelles"}),e.jsx("div",{className:"container","data-aos":"fade-up","data-aos-delay":"100",children:e.jsx("div",{className:"row g-4 justify-content-center",children:a.map((r,m)=>e.jsx("div",{className:"col-lg-4 col-md-6","data-aos":"zoom-in","data-aos-delay":100+m*100,children:e.jsxs(g,{to:"/courses",className:`category-card ${r.className}`,style:{display:"block",height:"100%",padding:"30px",textAlign:"center"},children:[e.jsx("div",{className:"category-icon",style:{fontSize:"3rem",marginBottom:"20px"},children:e.jsx("i",{className:`bi ${r.icon}`})}),e.jsx("h3",{style:{marginBottom:"15px"},children:r.name}),e.jsx("p",{style:{marginBottom:"15px",fontSize:"0.95rem"},children:r.description}),e.jsxs("span",{className:"course-count",style:{display:"inline-block",padding:"8px 20px",backgroundColor:"rgba(255,255,255,0.1)",borderRadius:"20px",fontSize:"0.9rem"},children:[r.courseCount," Programmes disponibles"]})]})},r.id))})})]})},A=()=>{const[a,r]=n.useState("licence"),[m,h]=n.useState([]),[t,l]=n.useState(!0),[f,N]=n.useState(null),x={licence:"/assets/img/education/students-9.webp",master:"/assets/img/education/activities-3.webp",ingenierie:"/assets/img/education/courses-12.webp"};n.useEffect(()=>{(async()=>{try{l(!0);const c=await w();h(c)}catch(c){console.error("Erreur chargement filières:",c),N("Impossible de charger les filières")}finally{l(!1)}})()},[]);const i=s=>{const c=s.toLowerCase().trim();return c.includes("licence")?"licence":c.includes("master")?"master":c.includes("ing")?"ingenierie":c},o=m.filter(s=>i(s.cycle)===a),d=s=>{switch(s){case"inscriptions-ouvertes":return"Ouvert";case"inscriptions-fermees":return"Fermé";case"prochainement":return"Bientôt";default:return""}},p=s=>{switch(s){case"inscriptions-ouvertes":return"bg-success text-white";case"inscriptions-fermees":return"bg-secondary text-white";case"prochainement":return"bg-warning text-dark";default:return"bg-light text-dark"}},j=s=>new Date(s).toLocaleDateString("fr-FR",{day:"numeric",month:"short",year:"numeric"}),u=s=>s.badge==="inscriptions-ouvertes";return t?e.jsxs("section",{id:"featured-courses",className:"featured-courses section",children:[e.jsx(b,{title:"Nos Filières",subtitle:"Découvrez nos programmes d'excellence dans les trois cycles de formation"}),e.jsx("div",{className:"container",children:e.jsxs("div",{className:"text-center py-5",children:[e.jsx("div",{className:"spinner-border text-primary",role:"status",children:e.jsx("span",{className:"visually-hidden",children:"Chargement..."})}),e.jsx("p",{className:"mt-3 text-muted",children:"Chargement des filières..."})]})})]}):e.jsxs("section",{id:"featured-courses",className:"featured-courses section",children:[e.jsx(b,{title:"Nos Filières",subtitle:"Découvrez nos programmes d'excellence dans les trois cycles de formation"}),e.jsxs("div",{className:"container","data-aos":"fade-up","data-aos-delay":"100",children:[e.jsx("div",{className:"tabs-container mb-5","data-aos":"fade-up","data-aos-delay":"150",children:e.jsx("div",{className:"row justify-content-center",children:e.jsx("div",{className:"col-lg-8",children:e.jsxs("div",{className:"nav nav-pills justify-content-center border rounded-pill p-2 bg-light",children:[e.jsx("button",{className:`nav-link rounded-pill ${a==="licence"?"active bg-primary text-white":"text-dark"}`,onClick:()=>r("licence"),children:"Licence"}),e.jsx("button",{className:`nav-link rounded-pill ${a==="master"?"active bg-primary text-white":"text-dark"}`,onClick:()=>r("master"),children:"Master"}),e.jsx("button",{className:`nav-link rounded-pill ${a==="ingenierie"?"active bg-primary text-white":"text-dark"}`,onClick:()=>r("ingenierie"),children:"Ingénierie"})]})})})}),e.jsxs("div",{className:"tab-content",children:[f&&e.jsx("div",{className:"alert alert-warning text-center",role:"alert",children:f}),o.length===0&&!f&&e.jsx("div",{className:"text-center py-5",children:e.jsx("p",{className:"text-muted",children:"Aucune filière disponible pour ce cycle."})}),o.length>0&&e.jsx("div",{className:"row g-4",children:o.map((s,c)=>e.jsx("div",{className:"col-xl-3 col-lg-4 col-md-6","data-aos":"fade-up","data-aos-delay":200+c*50,children:e.jsxs("div",{className:"card h-100 border",children:[e.jsxs("div",{className:"position-relative",children:[e.jsx("img",{src:s.image||x[s.cycle]||"/assets/img/education/students-9.webp",alt:s.title,className:"card-img-top",style:{height:"160px",objectFit:"cover"}}),e.jsx("div",{className:`position-absolute top-0 end-0 m-2 badge ${p(s.badge||"")}`,children:d(s.badge||"")})]}),e.jsxs("div",{className:"card-body d-flex flex-column",children:[e.jsx("div",{className:"mb-2",children:e.jsx("small",{className:"text-muted text-uppercase fw-bold",children:s.cycle})}),e.jsx("h6",{className:"card-title mb-3 fw-bold text-dark",children:s.title}),s.dateLimite&&e.jsx("div",{className:"mt-auto",children:e.jsxs("div",{className:"d-flex align-items-center text-muted mb-3",children:[e.jsx("i",{className:"bi bi-calendar3 me-2 small"}),e.jsxs("small",{className:"fw-medium",children:["Clôture : ",j(s.dateLimite)]})]})}),e.jsx("div",{className:"d-grid",children:u(s)?e.jsxs(g,{to:"/enroll",className:"btn btn-primary btn-sm",children:[e.jsx("i",{className:"bi bi-pencil-square me-2"}),"Candidater"]}):e.jsxs("button",{className:"btn btn-outline-secondary btn-sm",disabled:!0,children:[e.jsx("i",{className:"bi bi-lock me-2"}),"Inscriptions fermées"]})})]})]})},s.id))})]})]})]})},T=()=>e.jsxs(e.Fragment,{children:[e.jsx(k,{}),e.jsx(z,{}),e.jsx(I,{}),e.jsx(S,{}),e.jsx(A,{})]});export{T as default};
