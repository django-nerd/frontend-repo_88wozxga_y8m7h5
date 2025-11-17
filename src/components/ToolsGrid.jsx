import { Cpu, Gauge, Battery, Wrench, Settings2, Camera } from 'lucide-react'

const tools = [
  { id: 'psu', name: 'PSU Calculator', icon: Battery, desc: 'Correct wattage with safe headroom.' },
  { id: 'bottleneck', name: 'Bottleneck Calculator', icon: Gauge, desc: 'Spot CPU/GPU mismatches fast.' },
  { id: 'compat', name: 'Compatibility Checker', icon: Settings2, desc: 'Quick sanity checks for a stable build.' },
  { id: 'fps', name: 'FPS Estimator', icon: Camera, desc: 'Get a ballpark FPS for your setup.' },
  { id: 'upgrade', name: 'Upgrade Advisor', icon: Wrench, desc: 'Targeted upgrade ideas for your goals.' },
  { id: 'optimizer', name: 'AI Build Optimizer', icon: Cpu, desc: 'Gemini-powered personalized plan.' },
]

export default function ToolsGrid() {
  return (
    <section id="tools" className="bg-[#070b14] py-12 md:py-16">
      <div className="container mx-auto px-6">
        <h2 className="text-2xl md:text-3xl font-bold text-slate-100 mb-6">All Tools</h2>
        <div className="grid sm:grid-cols-2 lg:grid-cols-3 gap-4">
          {tools.map(t => (
            <a key={t.id} href="#tool" className="group rounded-2xl border border-white/10 bg-white/5 hover:bg-white/10 transition p-5 flex items-start gap-4">
              <t.icon className="w-6 h-6 text-blue-400 group-hover:scale-110 transition" />
              <div>
                <div className="text-slate-100 font-semibold">{t.name}</div>
                <div className="text-slate-400 text-sm">{t.desc}</div>
              </div>
            </a>
          ))}
        </div>
      </div>
    </section>
  )
}
