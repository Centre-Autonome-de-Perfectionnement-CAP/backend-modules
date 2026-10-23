import{j as a}from"./index-Dlag6U1b.js";const b=({label:l,count:o,color:e,bg:d,icon:i,blink:m=!1,active:n=!1,onClick:t})=>{const s=m&&o>0;return a.jsxs("div",{onClick:t,role:t?"button":void 0,tabIndex:t?0:void 0,onKeyDown:t?r=>{(r.key==="Enter"||r.key===" ")&&(r.preventDefault(),t())}:void 0,className:`dmd-stat${t?" dmd-stat--click":""}${s?" dmd-stat--blink":""}`,style:{"--stat-color":e,"--stat-bg":d,position:"relative",height:"100%",minHeight:64,display:"flex",alignItems:"center",gap:10,padding:"10px 12px",background:"#fff",borderRadius:12,border:`1px solid ${n||s?e:"#e6ebf2"}`,boxShadow:n?`0 0 0 3px ${e}22, 0 6px 18px rgba(15,23,42,0.06)`:"0 1px 3px rgba(15,23,42,0.05)",cursor:t?"pointer":"default",outline:"none",overflow:"hidden"},children:[a.jsx("style",{children:`
        .dmd-stat { transition: transform .15s ease, box-shadow .15s ease, border-color .15s ease; }
        .dmd-stat::before {
          content: ''; position: absolute; left: 0; top: 0; bottom: 0; width: 4px;
          background: var(--stat-color); opacity: .9;
        }
        .dmd-stat--click:hover { transform: translateY(-2px); box-shadow: 0 10px 24px rgba(15,23,42,0.09); }
        .dmd-stat--click:focus-visible { box-shadow: 0 0 0 3px rgba(37,99,235,.35); }
        .dmd-stat--blink { animation: dmdStatBlink 1.4s ease-in-out infinite; }
        @keyframes dmdStatBlink {
          0%, 100% { box-shadow: 0 0 0 0 color-mix(in srgb, var(--stat-color) 45%, transparent); background: #fff; }
          50%      { box-shadow: 0 0 0 5px color-mix(in srgb, var(--stat-color) 0%, transparent); background: var(--stat-bg); }
        }
        .dmd-stat-dot {
          position: absolute; top: 8px; right: 8px; width: 8px; height: 8px; border-radius: 50%;
          background: var(--stat-color);
        }
        .dmd-stat-dot::after {
          content: ''; position: absolute; inset: 0; border-radius: 50%;
          background: var(--stat-color); animation: dmdStatDot 1.4s ease-out infinite;
        }
        @keyframes dmdStatDot { from { transform: scale(1); opacity: .7 } to { transform: scale(2.6); opacity: 0 } }
        @media (prefers-reduced-motion: reduce) {
          .dmd-stat--blink, .dmd-stat-dot::after { animation: none; }
        }
      `}),i&&a.jsx("span",{style:{width:34,height:34,borderRadius:10,flexShrink:0,display:"flex",alignItems:"center",justifyContent:"center",background:d,color:e},children:i}),a.jsxs("div",{style:{minWidth:0},children:[a.jsx("div",{style:{fontSize:"1.3rem",fontWeight:800,lineHeight:1.05,color:o>0?"#0f172a":"#94a3b8",letterSpacing:"-0.02em"},children:o}),a.jsx("div",{style:{marginTop:2,fontSize:"0.875rem",fontWeight:600,lineHeight:1.2,color:"#64748b"},children:l})]}),s&&a.jsx("span",{className:"dmd-stat-dot","aria-hidden":"true"})]})};export{b as S};
