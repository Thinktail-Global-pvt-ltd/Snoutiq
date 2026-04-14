import { Send, X } from "lucide-react";

export default function NewDoctorWhatsAppPopup({
  isOpen = true,
  onClose = () => {},
  to = "9876543210",
  message = "Hi, this is a sample WhatsApp message preview.",
  label = "WhatsApp Message",
}) {
  if (!isOpen) {
    return null;
  }

  return (
    <div className="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/45 p-4 backdrop-blur-md">
      <div className="w-full max-w-[360px] overflow-hidden rounded-[1.7rem] border border-white/25 bg-[#E5DDD5] shadow-[0_32px_70px_rgba(15,23,42,0.28)]">
        <div className="bg-[#075E54] p-4 text-white">
          <div className="flex items-center gap-2">
            <div className="flex h-9 w-9 items-center justify-center rounded-full bg-white/75 text-sm font-black text-[#075E54]">
              A
            </div>
            <div>
              <p className="text-xs font-medium opacity-70">{label}</p>
              <p className="text-sm font-bold leading-tight">{to}</p>
            </div>
            <button
              type="button"
              onClick={onClose}
              className="ml-auto rounded-full p-2 transition hover:bg-white/10"
            >
              <X size={16} />
            </button>
          </div>
        </div>

        <div className="relative min-h-[210px] space-y-4 p-4">
          <div
            className="pointer-events-none absolute inset-0 opacity-[0.06]"
            style={{
              backgroundImage: "radial-gradient(#0f172a 1px, transparent 1px)",
              backgroundSize: "20px 20px",
            }}
          />

          <div className="relative ml-auto max-w-[86%]">
            <div className="absolute -right-2 top-0 h-0 w-0 border-r-[10px] border-r-transparent border-t-[10px] border-t-[#dcf8c6]" />
            <div className="rounded-2xl rounded-tr-none bg-[#dcf8c6] p-3 shadow-sm">
              <p className="text-[11px] font-bold uppercase tracking-[0.14em] text-[#075E54]">
                Preview
              </p>
              <p className="mt-2 text-sm leading-6 text-slate-800">{message}</p>
              <p className="mt-2 text-right text-[10px] text-slate-500">10:30 AM</p>
            </div>
          </div>

          <div className="relative max-w-[82%]">
            <div className="absolute -left-2 top-0 h-0 w-0 border-l-[10px] border-l-transparent border-t-[10px] border-t-white" />
            <div className="rounded-2xl rounded-tl-none bg-white p-3 shadow-sm">
              <p className="text-sm text-slate-700">Received. The pet is improving and appetite is back.</p>
              <p className="mt-2 text-right text-[10px] text-slate-400">10:32 AM</p>
            </div>
          </div>
        </div>

        <div className="flex items-center gap-2 bg-[#F0F2F5] p-3">
          <div className="flex-1 rounded-full bg-white px-4 py-2.5 text-sm text-slate-400">
            Type a message
          </div>

          <button
            type="button"
            className="flex h-11 w-11 items-center justify-center rounded-full bg-[#128C7E] text-white"
          >
            <Send size={18} />
          </button>
        </div>
      </div>
    </div>
  );
}
