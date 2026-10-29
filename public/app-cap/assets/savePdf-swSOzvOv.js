const d=(o,t)=>{const e=document.createElement("a");e.href=o,e.download=t,document.body.appendChild(e),e.click(),e.remove(),window.setTimeout(()=>URL.revokeObjectURL(o),3e4)};export{d as s};
