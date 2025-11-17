import Hero from './components/Hero'
import ToolPanel from './components/ToolPanel'
import ToolsGrid from './components/ToolsGrid'

function App() {
  return (
    <div className="min-h-screen w-full bg-[#0a0f19] text-white">
      <Hero />
      <ToolPanel />
      <ToolsGrid />
      <footer className="bg-[#060913] border-t border-white/10 py-8 text-center text-slate-400 text-sm">Built with love for PC builders • AI by Gemini</footer>
    </div>
  )
}

export default App
