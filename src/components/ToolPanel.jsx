import { useState } from 'react'

const BACKEND = import.meta.env.VITE_BACKEND_URL || 'http://localhost:8000'

export default function ToolPanel() {
  const [tab, setTab] = useState('psu')
  return (
    <section id="tool" className="relative w-full bg-[#070b14] py-10 md:py-14">
      <div className="container mx-auto px-6 grid lg:grid-cols-2 gap-8">
        <div className="bg-white/5 backdrop-blur border border-white/10 rounded-2xl p-6">
          <Tabs tab={tab} setTab={setTab} />
          <div className="mt-6">
            {tab === 'psu' && <PSUCalculator />}
            {tab === 'bottleneck' && <Bottleneck />}
            {tab === 'compat' && <Compatibility />}
            {tab === 'fps' && <FPSEstimator />}
            {tab === 'upgrade' && <UpgradeAdvisor />}
            {tab === 'optimizer' && <AIOptimizer />}
          </div>
        </div>
        <div className="space-y-6">
          <h3 className="text-xl md:text-2xl font-semibold text-slate-100">How it works</h3>
          <p className="text-slate-300">Pick a tool to get instant insights. The AI Optimizer uses Gemini to tailor recommendations to your budget and goals.</p>
          <ul className="text-slate-300 list-disc pl-5 space-y-2">
            <li>Accurate PSU sizing with headroom</li>
            <li>Quick bottleneck check for CPU/GPU balance</li>
            <li>Fast compatibility sanity checks</li>
            <li>Estimated FPS for common scenarios</li>
            <li>Upgrade advice aligned to your targets</li>
          </ul>
        </div>
      </div>
    </section>
  )
}

function Tabs({ tab, setTab }) {
  const tabs = [
    { id: 'psu', label: 'PSU Calculator' },
    { id: 'bottleneck', label: 'Bottleneck' },
    { id: 'compat', label: 'Compatibility' },
    { id: 'fps', label: 'FPS Estimator' },
    { id: 'upgrade', label: 'Upgrade Advisor' },
    { id: 'optimizer', label: 'AI Build Optimizer' },
  ]
  return (
    <div className="flex flex-wrap gap-2">
      {tabs.map(t => (
        <button key={t.id} onClick={() => setTab(t.id)} className={`px-3 py-2 rounded-lg text-sm font-medium transition border ${tab===t.id?'bg-gradient-to-r from-blue-600 to-purple-600 text-white border-transparent':'bg-white/5 text-slate-300 border-white/10 hover:bg-white/10'}`}>
          {t.label}
        </button>
      ))}
    </div>
  )
}

function PSUCalculator() {
  const [form, setForm] = useState({
    cpu: '', cpuTdp: '', gpu: '', gpuTdp: '', overclocking: false, peripherals: 30, headroom: 30,
  })
  const [result, setResult] = useState(null)
  const calc = async () => {
    const parts = []
    if (form.cpu) parts.push({ type: 'cpu', name: form.cpu, tdp: parseInt(form.cpuTdp||'0')||undefined })
    if (form.gpu) parts.push({ type: 'gpu', name: form.gpu, tdp: parseInt(form.gpuTdp||'0')||undefined })
    const res = await fetch(`${BACKEND}/api/tools/psu`, { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ parts, overclocking: form.overclocking, peripherals_watts: Number(form.peripherals), headroom_percent: Number(form.headroom) }) })
    setResult(await res.json())
  }
  return (
    <div className="space-y-4">
      <div className="grid grid-cols-2 gap-3">
        <input placeholder="CPU (e.g., Ryzen 5 5600)" className="input" value={form.cpu} onChange={e=>setForm({...form, cpu:e.target.value})} />
        <input placeholder="CPU TDP (W)" className="input" value={form.cpuTdp} onChange={e=>setForm({...form, cpuTdp:e.target.value})} />
        <input placeholder="GPU (e.g., RTX 3060)" className="input" value={form.gpu} onChange={e=>setForm({...form, gpu:e.target.value})} />
        <input placeholder="GPU TDP (W)" className="input" value={form.gpuTdp} onChange={e=>setForm({...form, gpuTdp:e.target.value})} />
        <label className="col-span-2 inline-flex items-center gap-2 text-slate-200"><input type="checkbox" checked={form.overclocking} onChange={e=>setForm({...form, overclocking:e.target.checked})} /> Overclocking</label>
        <input placeholder="Peripherals (W)" className="input" value={form.peripherals} onChange={e=>setForm({...form, peripherals:e.target.value})} />
        <input placeholder="Headroom (%)" className="input" value={form.headroom} onChange={e=>setForm({...form, headroom:e.target.value})} />
      </div>
      <button onClick={calc} className="btn-primary">Calculate</button>
      {result && (
        <div className="card">
          <div className="grid grid-cols-2 gap-2 text-slate-200 text-sm">
            <span>Estimated Load:</span><span className="text-right font-semibold">{result.estimated_load_w} W</span>
            <span>Recommended PSU:</span><span className="text-right font-semibold">{result.recommended_w} W</span>
          </div>
        </div>
      )}
    </div>
  )
}

function Bottleneck() {
  const [form, setForm] = useState({ cpu: '', gpu: '', ram: 16, resolution: '1080p' })
  const [result, setResult] = useState(null)
  const run = async () => {
    const res = await fetch(`${BACKEND}/api/tools/bottleneck`, { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ cpu: form.cpu, gpu: form.gpu, memory_gb: Number(form.ram), resolution: form.resolution }) })
    setResult(await res.json())
  }
  return (
    <div className="space-y-4">
      <div className="grid grid-cols-2 gap-3">
        <input placeholder="CPU" className="input" value={form.cpu} onChange={e=>setForm({...form, cpu:e.target.value})} />
        <input placeholder="GPU" className="input" value={form.gpu} onChange={e=>setForm({...form, gpu:e.target.value})} />
        <input placeholder="RAM (GB)" className="input" value={form.ram} onChange={e=>setForm({...form, ram:e.target.value})} />
        <select className="input" value={form.resolution} onChange={e=>setForm({...form, resolution:e.target.value})}>
          <option>1080p</option><option>1440p</option><option>4k</option>
        </select>
      </div>
      <button onClick={run} className="btn-primary">Check</button>
      {result && <div className="card text-slate-200 text-sm">Status: <b className="uppercase">{result.status}</b> — {result.note}</div>}
    </div>
  )
}

function Compatibility() {
  const [text, setText] = useState('CPU, GPU, Motherboard, RAM, PSU')
  const [result, setResult] = useState(null)
  const parseParts = () => text.split(',').map(s=>({ type: s.trim().split(' ')[0].toLowerCase(), name: s.trim(), tdp: undefined }))
  const run = async () => {
    const res = await fetch(`${BACKEND}/api/tools/compatibility`, { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ parts: parseParts() }) })
    setResult(await res.json())
  }
  return (
    <div className="space-y-4">
      <textarea className="input min-h-[120px]" value={text} onChange={e=>setText(e.target.value)} />
      <button onClick={run} className="btn-primary">Check</button>
      {result && <div className="card text-slate-200 text-sm">{result.compatible? 'Looks compatible ✅' : `Issues: ${result.issues.join('; ')}`}</div>}
    </div>
  )
}

function FPSEstimator() {
  const [form, setForm] = useState({ cpu_class: 'mid', gpu_class: 'mid', resolution: '1080p', game_profile: 'esports' })
  const [result, setResult] = useState(null)
  const run = async () => {
    const res = await fetch(`${BACKEND}/api/tools/fps`, { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify(form) })
    setResult(await res.json())
  }
  return (
    <div className="space-y-4">
      <div className="grid grid-cols-2 gap-3">
        <Select value={form.cpu_class} onChange={(v)=>setForm({...form, cpu_class:v})} options={['low','mid','high','ultra']} label="CPU Class" />
        <Select value={form.gpu_class} onChange={(v)=>setForm({...form, gpu_class:v})} options={['low','mid','high','ultra']} label="GPU Class" />
        <Select value={form.resolution} onChange={(v)=>setForm({...form, resolution:v})} options={['1080p','1440p','4k']} label="Resolution" />
        <Select value={form.game_profile} onChange={(v)=>setForm({...form, game_profile:v})} options={['esports','aaa','rtx']} label="Game Profile" />
      </div>
      <button onClick={run} className="btn-primary">Estimate</button>
      {result && <div className="card text-slate-200 text-sm">Estimated FPS: <b>{result.estimated_fps}</b></div>}
    </div>
  )
}

function UpgradeAdvisor() {
  const [form, setForm] = useState({ target_resolution: '1080p', use_case: 'gaming', parts: [] })
  const [result, setResult] = useState(null)
  const [cpu, setCpu] = useState('')
  const [gpu, setGpu] = useState('')
  const add = () => {
    const arr = [...form.parts]
    if (cpu) arr.push({ type: 'cpu', name: cpu, tdp: undefined })
    if (gpu) arr.push({ type: 'gpu', name: gpu, tdp: undefined })
    setForm({ ...form, parts: arr })
    setCpu(''); setGpu('')
  }
  const run = async () => {
    const res = await fetch(`${BACKEND}/api/tools/upgrade`, { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify(form) })
    setResult(await res.json())
  }
  return (
    <div className="space-y-4">
      <div className="grid grid-cols-2 gap-3">
        <input placeholder="CPU" className="input" value={cpu} onChange={e=>setCpu(e.target.value)} />
        <input placeholder="GPU" className="input" value={gpu} onChange={e=>setGpu(e.target.value)} />
        <select className="input" value={form.target_resolution} onChange={e=>setForm({...form, target_resolution:e.target.value})}>
          <option>1080p</option><option>1440p</option><option>4k</option>
        </select>
        <select className="input" value={form.use_case} onChange={e=>setForm({...form, use_case:e.target.value})}>
          <option>gaming</option><option>workstation</option><option>mixed</option>
        </select>
      </div>
      <button onClick={add} className="btn-secondary">Add Parts</button>
      <button onClick={run} className="btn-primary">Get Advice</button>
      {form.parts.length>0 && <div className="text-slate-300 text-sm">Parts: {form.parts.map(p=>p.name).join(', ')}</div>}
      {result && <div className="card text-slate-200 text-sm space-y-1">{result.suggestions.map((s,i)=>(<div key={i}>• {s}</div>))}</div>}
    </div>
  )
}

function AIOptimizer() {
  const [form, setForm] = useState({ budget: '', target_resolution: '1080p', target_fps: 120, use_case: 'gaming', parts: [] })
  const [cpu, setCpu] = useState('')
  const [gpu, setGpu] = useState('')
  const [tdp, setTdp] = useState('')
  const [result, setResult] = useState(null)
  const add = () => {
    const t = parseInt(tdp||'0')
    const arr = [...form.parts, { type: t>150?'gpu':'cpu', name: (t>150?gpu:cpu) || 'Part', tdp: t||undefined }]
    setForm({ ...form, parts: arr }); setCpu(''); setGpu(''); setTdp('')
  }
  const run = async () => {
    const body = { ...form, budget: form.budget? Number(form.budget): undefined }
    const res = await fetch(`${BACKEND}/api/tools/optimizer`, { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify(body) })
    setResult(await res.json())
  }
  return (
    <div className="space-y-4">
      <div className="grid grid-cols-2 gap-3">
        <input placeholder="Budget (USD)" className="input" value={form.budget} onChange={e=>setForm({...form, budget:e.target.value})} />
        <select className="input" value={form.target_resolution} onChange={e=>setForm({...form, target_resolution:e.target.value})}>
          <option>1080p</option><option>1440p</option><option>4k</option>
        </select>
        <input placeholder="Target FPS" className="input" value={form.target_fps} onChange={e=>setForm({...form, target_fps:e.target.value})} />
        <select className="input" value={form.use_case} onChange={e=>setForm({...form, use_case:e.target.value})}>
          <option>gaming</option><option>workstation</option><option>mixed</option>
        </select>
        <input placeholder="CPU name" className="input" value={cpu} onChange={e=>setCpu(e.target.value)} />
        <input placeholder="GPU name" className="input" value={gpu} onChange={e=>setGpu(e.target.value)} />
        <input placeholder="TDP (W) for last part" className="input" value={tdp} onChange={e=>setTdp(e.target.value)} />
      </div>
      <div className="flex gap-3">
        <button onClick={add} className="btn-secondary">Add Part</button>
        <button onClick={run} className="btn-primary">Optimize with AI</button>
      </div>
      {form.parts.length>0 && <div className="text-slate-300 text-sm">Parts: {form.parts.map(p=>`${p.type.toUpperCase()}: ${p.name} (${p.tdp||'n/a'}W)`).join(' • ')}</div>}
      {result && (
        <div className="card text-slate-200 text-sm space-y-3">
          <div className="opacity-80">{result.ai}</div>
          {result.psu && <div className="bg-white/5 rounded-lg p-3">PSU Suggestion: <b>{result.psu.recommended_w}W</b> (est. load {result.psu.estimated_load_w}W)</div>}
        </div>
      )}
      <p className="text-xs text-slate-400">Tip: set GEMINI_API_KEY to enable AI responses.</p>
    </div>
  )
}

function Select({ value, onChange, options, label }) {
  return (
    <label className="text-slate-300 text-sm flex flex-col gap-1">
      <span className="opacity-80">{label}</span>
      <select value={value} onChange={e=>onChange(e.target.value)} className="input">
        {options.map(o => <option key={o} value={o}>{o}</option>)}
      </select>
    </label>
  )
}

// Utility styles
// We keep tailwind classes here for brevity
// input, button, card, etc.
const base = 'w-full rounded-lg bg-white/5 border border-white/10 text-slate-100 placeholder:text-slate-400 focus:outline-none focus:ring-2 focus:ring-blue-500/50 focus:border-blue-500/50 px-3 py-2'
function Input(props){ return <input {...props} className={`input ${base} ${props.className||''}`} /> }

