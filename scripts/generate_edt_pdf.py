#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""
generate_edt_pdf.py  —  Generateur PDF emploi du temps UAC/EPAC/CAP

Usages :
  python generate_edt_pdf.py --json-file data.json --output edt.pdf
  python generate_edt_pdf.py '<json>' output.pdf   (legacy)
  python generate_edt_pdf.py                       (test avec donnees exemple)

Installation :  pip install reportlab
"""
import sys, os, json, argparse

# Encoding UTF-8 sur Windows
if sys.platform == 'win32':
    import io
    sys.stdout = io.TextIOWrapper(sys.stdout.buffer, encoding='utf-8', errors='replace')
    sys.stderr = io.TextIOWrapper(sys.stderr.buffer, encoding='utf-8', errors='replace')
    os.system('chcp 65001 > nul 2>&1')

try:
    from reportlab.lib.pagesizes import A4, landscape
    from reportlab.lib import colors
    from reportlab.lib.units import mm
    from reportlab.pdfgen import canvas as pdfcanvas
    from reportlab.platypus import SimpleDocTemplate, Table, TableStyle, Paragraph, Spacer
    from reportlab.lib.styles import ParagraphStyle
    from reportlab.lib.enums import TA_CENTER, TA_LEFT
except ImportError:
    print("ERREUR: reportlab non installe. Executez: pip install reportlab")
    sys.exit(1)

# Couleurs
GREEN  = colors.HexColor('#4CAF50')
BLUE   = colors.HexColor('#A8D8EA')
PINK   = colors.HexColor('#FF00FF')
GRAY   = colors.HexColor('#D9D9D9')
BORDER = colors.HexColor('#555555')
WHITE  = colors.white
BLACK  = colors.black

PALETTE = [
    colors.HexColor('#4CAF50'), colors.HexColor('#A8D8EA'),
    colors.HexColor('#FFD700'), colors.HexColor('#FF9800'),
    colors.HexColor('#CE93D8'), colors.HexColor('#80CBC4'),
    colors.HexColor('#F48FB1'), colors.HexColor('#B0BEC5'),
]

PW, PH = landscape(A4)
DAYS_FR    = {'monday':'LUNDI','tuesday':'MARDI','wednesday':'MERCREDI',
              'thursday':'JEUDI','friday':'VENDREDI','saturday':'SAMEDI','sunday':'DIMANCHE'}
DAYS_ORDER = ['monday','tuesday','wednesday','thursday','friday','saturday','sunday']


def safe(t):
    """Convertit en Latin-1 pour les polices built-in ReportLab."""
    if not t: return ''
    return str(t).encode('latin-1', errors='replace').decode('latin-1')


def S():
    return {
        'bold': ParagraphStyle('b',  fontName='Helvetica-Bold', fontSize=9,   leading=12, alignment=TA_CENTER),
        'norm': ParagraphStyle('n',  fontName='Helvetica',       fontSize=8,   leading=10, alignment=TA_CENTER),
        'sala': ParagraphStyle('sa', fontName='Helvetica-Bold', fontSize=8,   leading=10, alignment=TA_CENTER, textColor=PINK),
        'hour': ParagraphStyle('h',  fontName='Helvetica-Bold', fontSize=9,   leading=11, alignment=TA_CENTER),
        'head': ParagraphStyle('hd', fontName='Helvetica-Bold', fontSize=11,  leading=14, alignment=TA_CENTER),
        'leg':  ParagraphStyle('l',  fontName='Helvetica',       fontSize=8,   leading=10, alignment=TA_LEFT),
        'legb': ParagraphStyle('lb', fontName='Helvetica-Bold', fontSize=8,   leading=10, alignment=TA_LEFT),
        'smno': ParagraphStyle('sm', fontName='Helvetica',       fontSize=7.5, leading=9,  alignment=TA_CENTER),
        'nb':   ParagraphStyle('nb', fontName='Helvetica-Bold', fontSize=9,   leading=12, alignment=TA_LEFT),
        'sig':  ParagraphStyle('sg', fontName='Helvetica',       fontSize=9,   leading=12, alignment=TA_LEFT),
        'sigb': ParagraphStyle('sb', fontName='Helvetica-Bold', fontSize=9,   leading=12, alignment=TA_LEFT),
    }


def cell(course, st):
    parts = []
    n = safe(course.get('course_name',''))
    if n: parts.append(Paragraph(f'<b>{n}</b>', st['bold']))
    r = safe(course.get('room',''))
    if r: parts.append(Paragraph(f'({r})', st['sala']))
    for p in course.get('professors', []):
        ps = safe(p)
        if ps: parts.append(Paragraph(ps, st['smno']))
    t = safe(course.get('time_slot',''))
    if t: parts.append(Paragraph(f'<b>({t})</b>', st['hour']))
    return parts or [Paragraph('', st['norm'])]


def generate_pdf(data, out):
    st = S()
    sched = data.get('schedule', {})
    period  = safe(data.get('period', ''))
    cls     = safe(data.get('class_name', ''))
    sn1     = safe(data.get('school_name',  "UNIVERSITE D'ABOMEY-CALAVI"))
    sn2     = safe(data.get('school_name2', "ECOLE POLYTECHNIQUE D'ABOMEY-CALAVI"))
    sn3     = safe(data.get('school_name3', "CENTRE AUTONOME DE PERFECTIONNEMENT"))
    ref     = safe(data.get('ref_code',     "UAC/EPAC/CAP-RdivFC"))
    nb      = safe(data.get('nb_note', ''))
    sl      = safe(data.get('signature_left',  'Le Responsable Division Formation Continue'))
    nl      = safe(data.get('name_left', ''))
    sr      = safe(data.get('signature_right', 'Le Chef CAP'))
    nr      = safe(data.get('name_right', ''))

    active = [d for d in DAYS_ORDER if d in sched and sched[d]]
    if not active: active = list(DAYS_ORDER[:6])

    # Couleurs par matiere
    cmap, ci = {}, 0
    for d in active:
        for c in sched.get(d, []):
            k = c.get('course_name','')
            if k and k not in cmap:
                cmap[k] = PALETTE[ci % len(PALETTE)]
                ci += 1

    nc    = len(active)
    cw    = (PW - 30*mm) / nc
    mslot = max((len(sched.get(d,[])) for d in active), default=1)

    # Tableau
    rows = [[Paragraph(DAYS_FR.get(d,d.upper()), st['head']) for d in active]]
    for si in range(mslot):
        row = []
        for d in active:
            cs = sched.get(d, [])
            row.append(cell(cs[si], st) if si < len(cs) else [Paragraph('', st['norm'])])
        rows.append(row)

    ts_cmds = [
        ('BACKGROUND',(0,0),(-1,0),GRAY),
        ('ALIGN',(0,0),(-1,-1),'CENTER'),('VALIGN',(0,0),(-1,-1),'MIDDLE'),
        ('GRID',(0,0),(-1,-1),1.5,BORDER),
        ('TOPPADDING',(0,0),(-1,-1),5),('BOTTOMPADDING',(0,0),(-1,-1),5),
        ('LEFTPADDING',(0,0),(-1,-1),4),('RIGHTPADDING',(0,0),(-1,-1),4),
    ]
    for ri in range(1, mslot+1):
        for ci2,d in enumerate(active):
            cs = sched.get(d,[])
            if ri-1 < len(cs):
                k  = cs[ri-1].get('course_name','')
                ts_cmds.append(('BACKGROUND',(ci2,ri),(ci2,ri), cmap.get(k,WHITE)))

    tbl = Table(rows, colWidths=[cw]*nc, rowHeights=[12*mm]+[42*mm]*mslot)
    tbl.setStyle(TableStyle(ts_cmds))

    # Legende
    lr = [[Paragraph('<b>Legende (Couleur)</b>',st['legb']),
           Paragraph("<b>Attribution des Couleurs sur l'emploi du temps</b>",st['legb'])]]
    for k,co in cmap.items():
        lr.append([Paragraph('(Salle)',st['leg']), Paragraph(f'<b>{safe(k)}</b>',st['legb'])])
    lt = Table(lr, colWidths=[50*mm,100*mm])
    ls = TableStyle([('GRID',(0,0),(-1,-1),0.5,BORDER),('BACKGROUND',(0,0),(-1,0),GRAY),
                     ('VALIGN',(0,0),(-1,-1),'MIDDLE'),('TOPPADDING',(0,0),(-1,-1),2),
                     ('BOTTOMPADDING',(0,0),(-1,-1),2),('LEFTPADDING',(0,0),(-1,-1),4)])
    for i,(k,co) in enumerate(cmap.items()):
        ls.add('BACKGROUND',(0,i+1),(0,i+1),co)
    lt.setStyle(ls)

    # Signatures
    sig_tbl = Table([[[Paragraph(sl,st['sig']),Spacer(1,8*mm),Paragraph(nl,st['sigb'])],
                      [Paragraph(sr,st['sig']),Spacer(1,8*mm),Paragraph(nr,st['sigb'])]]],
                    colWidths=[(PW-30*mm)/2]*2)
    sig_tbl.setStyle(TableStyle([('VALIGN',(0,0),(-1,-1),'TOP'),
                                 ('ALIGN',(0,0),(0,-1),'LEFT'),('ALIGN',(1,0),(1,-1),'RIGHT')]))

    def header(c, doc):
        c.saveState()
        c.setFont('Helvetica-Bold',13); c.drawCentredString(PW/2, PH-18*mm, sn1)
        c.setFont('Helvetica-Bold',11); c.drawCentredString(PW/2, PH-25*mm, sn2)
        c.drawCentredString(PW/2, PH-31*mm, sn3)
        c.setFont('Helvetica',10); c.drawCentredString(PW/2, PH-37*mm, '.......................................')
        c.setFont('Helvetica-Bold',9); c.drawString(15*mm, PH-44*mm, ref)
        rw = c.stringWidth(ref,'Helvetica-Bold',9)
        c.setLineWidth(0.5); c.line(15*mm, PH-45*mm, 15*mm+rw, PH-45*mm)
        c.setFont('Helvetica-Bold',12)
        c.drawCentredString(PW/2, PH-51*mm, f'Emploi du temps {cls}')
        if period:
            c.setFont('Helvetica-Bold',11)
            pt = f'du {period}'
            tw = c.stringWidth(pt,'Helvetica-Bold',11)
            px, py = PW/2-tw/2, PH-58*mm
            c.setFillColor(colors.HexColor('#FFFF00')); c.rect(px-3, py-3, tw+6, 15, fill=1, stroke=0)
            c.setFillColor(BLACK); c.drawString(px, py, pt)
        c.restoreState()

    doc = SimpleDocTemplate(out, pagesize=landscape(A4),
                             leftMargin=15*mm, rightMargin=15*mm,
                             topMargin=65*mm, bottomMargin=10*mm)
    nb_p = Paragraph(f'<b>NB : {nb}</b>', st['nb']) if nb else Spacer(1,1*mm)
    doc.build([tbl, Spacer(1,4*mm), nb_p, Spacer(1,3*mm), lt, Spacer(1,6*mm), sig_tbl],
              onFirstPage=header, onLaterPages=header)
    print(f'PDF genere: {out}')


def main():
    p = argparse.ArgumentParser()
    p.add_argument('--json-file')
    p.add_argument('--output')
    p.add_argument('json_inline', nargs='?')
    p.add_argument('output_pos',  nargs='?')
    a = p.parse_args()

    if a.json_file:
        with open(a.json_file, encoding='utf-8') as f:
            data = json.load(f)
        out = a.output or 'edt.pdf'
    elif a.json_inline:
        data = json.loads(a.json_inline)
        out  = a.output_pos or a.output or 'edt.pdf'
    else:
        # Donnees de test
        data = {
            'school_name':'UNIVERSITE D ABOMEY-CALAVI',
            'school_name2':"ECOLE POLYTECHNIQUE D'ABOMEY-CALAVI",
            'school_name3':'CENTRE AUTONOME DE PERFECTIONNEMENT',
            'ref_code':'UAC/EPAC/CAP-RdivFC',
            'class_name':'des annees preparatoires (GE & GME)',
            'period':'23/03/26 au 13/04/26',
            'nb_note':"Mathematiques: 50H / Initiation a l'Algorithmique: 40H",
            'signature_left':'Le Responsable Division Formation Continue',
            'name_left':'Dr Ir Max Frejus Owolabi SANYA',
            'signature_right':'Le Chef CAP', 'name_right':'Professeur Fidele Paul TCHOBO',
            'schedule':{
                'monday':   [{'course_name':"Initiation a l'Algorithmique",'room':'Salle CAP 37','professors':['Dr SANYA Max Frejus','0161332652'],'time_slot':'18h-22h'}],
                'tuesday':  [{'course_name':'Mathematiques','room':'Salle CAP 37','professors':['Dr SABI TAKOU Daniel','66464876'],'time_slot':'18h-22h'}],
                'wednesday':[{'course_name':"Initiation a l'Algorithmique",'room':'Salle CAP 37','professors':['Dr SANYA Max Frejus','0161332652'],'time_slot':'18h-22h'}],
                'thursday': [{'course_name':'Mathematiques','room':'Salle CAP 37','professors':['Dr SABI TAKOU Daniel','66464876'],'time_slot':'18h-22h'}],
                'friday':   [{'course_name':"Initiation a l'Algorithmique",'room':'Salle CAP 37','professors':['Dr SANYA Max Frejus','0161332652'],'time_slot':'18h-22h'}],
                'saturday': [{'course_name':'Mathematiques','room':'Salle CAP 37','professors':['Dr SABI TAKOU Daniel','66464876'],'time_slot':'08h-12h'}],
            }
        }
        out = 'test_edt.pdf'

    generate_pdf(data, out)


if __name__ == '__main__':
    main()