import Spline from '@splinetool/react-spline'
import { motion } from 'framer-motion'

export default function Hero() {
  return (
    <section className="relative min-h-[60vh] md:min-h-[70vh] w-full overflow-hidden bg-[#0b0f1a]">
      <div className="absolute inset-0">
        <Spline scene="https://prod.spline.design/Y7DK6OtMHusdC345/scene.splinecode" style={{ width: '100%', height: '100%' }} />
      </div>
      <div className="relative z-10 container mx-auto px-6 py-24 md:py-28">
        <motion.div
          initial={{ opacity: 0, y: 20 }}
          animate={{ opacity: 1, y: 0 }}
          transition={{ duration: 0.6 }}
          className="max-w-3xl text-center mx-auto"
        >
          <h1 className="text-4xl md:text-6xl font-extrabold tracking-tight bg-gradient-to-r from-blue-400 via-purple-400 to-fuchsia-400 bg-clip-text text-transparent">
            Ultimate PC Builder Toolkit
          </h1>
          <p className="mt-4 md:mt-6 text-base md:text-lg text-slate-300">
            Bottleneck Calculator, Upgrade Advisor, PSU Calculator, Compatibility Checker, AI Build Optimizer, and FPS Estimator — all in one place.
          </p>
          <div className="mt-8 flex items-center justify-center gap-4">
            <a href="#tool" className="inline-flex items-center rounded-lg bg-gradient-to-r from-blue-600 to-purple-600 px-5 py-3 text-white font-semibold shadow hover:opacity-95 transition">
              Start Optimizing
            </a>
            <a href="#tools" className="inline-flex items-center rounded-lg bg-white/10 px-5 py-3 text-slate-200 font-semibold shadow hover:bg-white/15 transition">
              Explore Tools
            </a>
          </div>
        </motion.div>
      </div>
      <div className="pointer-events-none absolute inset-0 bg-[radial-gradient(ellipse_at_center,rgba(56,189,248,0.08),transparent_60%),radial-gradient(ellipse_at_bottom_right,rgba(168,85,247,0.10),transparent_60%)]" />
    </section>
  )
}
