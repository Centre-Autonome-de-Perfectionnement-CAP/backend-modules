import{j as e,d as p}from"./index-BSL11LE3.js";import{c as r}from"./cil-inbox-Ce4iVY04.js";const d=({title:s,subtitle:i,actions:a})=>e.jsxs("div",{className:"stg-head",children:[e.jsxs("div",{children:[e.jsx("h4",{style:{margin:0,fontWeight:800},children:s}),i&&e.jsx("span",{style:{color:"#64748b",fontSize:".88rem"},children:i})]}),a&&e.jsx("div",{style:{display:"flex",alignItems:"center",gap:8,flexWrap:"wrap"},children:a})]}),n=({text:s})=>e.jsxs("div",{style:{padding:"36px 12px",textAlign:"center",color:"#64748b"},children:[e.jsx(p,{icon:r,style:{width:32,color:"#cbd5e1"}}),e.jsx("div",{style:{marginTop:8},children:s})]}),o=({color:s,bg:i,children:a})=>e.jsx("span",{className:"stg-pill",style:{color:s,background:i},children:a}),x=()=>e.jsx("style",{children:`
    .stg-page { display: flex; flex-direction: column; gap: 14px; }
    .stg-head { display: flex; justify-content: space-between; align-items: flex-end; gap: 12px; flex-wrap: wrap; }
    .stg-cards { display: grid; grid-template-columns: repeat(auto-fit, minmax(140px, 1fr)); gap: 10px; }
    .stg-pill { display: inline-flex; align-items: center; gap: 4px; font-size: .7rem; font-weight: 800; padding: 2px 9px; border-radius: 999px; white-space: nowrap; }
    .stg-chip { display: inline-flex; align-items: center; gap: 4px; font-size: .74rem; font-weight: 600; padding: 2px 8px; border-radius: 8px; background: #f1f5f9; color: #334155; }
    .stg-err { background: #fff1f2; border: 1px solid #fecdd3; color: #9f1239; border-radius: 10px; padding: 10px 14px; margin-bottom: 10px; }
    .stg-list { display: flex; flex-direction: column; gap: 8px; }
    .stg-card { background: #fff; border: 1px solid #e6ebf2; border-radius: 12px; padding: 12px 14px; }
    .stg-row2 { display: flex; align-items: center; gap: 12px; }
    .stg-muted { color: #64748b; font-size: .8rem; }
    .stg-bar-sel { position: sticky; bottom: 12px; z-index: 5; display: flex; flex-wrap: wrap; align-items: center; gap: 8px;
      background: #0f766e; color: #fff; border-radius: 12px; padding: 10px 14px; box-shadow: 0 10px 24px rgba(15,118,110,.25); }
    .stg-bar-sel .btn { --cui-btn-color: #0f766e; }
    @media (max-width: 640px) { .stg-row2 { flex-wrap: wrap; } }
  `});export{n as E,d as P,x as S,o as a};
