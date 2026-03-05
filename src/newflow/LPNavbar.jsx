import { useNavigate } from "react-router-dom";
import { Button } from "./NewButton";
import logo from "../assets/images/logo.webp";

export function LPNavbar({ consultPath = "/parents", onConsultClick }) {
  const navigate = useNavigate();

  const go = (to) => {
    if (/^https?:\/\//i.test(to)) {
      window.open(to, "_blank", "noopener,noreferrer");
      return;
    }
    const target = String(to || "").trim();
    if (!target) return;

    const normalizedTarget =
      target.startsWith("/") || target.startsWith("#") ? target : `/${target}`;

    // Keep consult route navigation consistent with main Navbar behavior.
    if (normalizedTarget.includes("start=details")) {
      window.location.assign(normalizedTarget);
      return;
    }

    if (normalizedTarget.startsWith("#")) {
      navigate(normalizedTarget);
      return;
    }

    const [pathnamePart, queryPart = ""] = normalizedTarget.split("?");
    navigate({
      pathname: pathnamePart || "/",
      search: queryPart ? `?${queryPart}` : "",
    });
  };

  const handleConsultClick = () => {
    if (typeof onConsultClick === "function") {
      onConsultClick();
      return;
    }
    go(consultPath);
  };

  return (
    <nav className="sticky top-0 z-50 w-full border-b border-brand/20 bg-white/80 backdrop-blur-md">
      <div className="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        <div className="flex h-16 items-center justify-between">
          <div className="flex items-center">
            <a
              href="https://www.snoutiq.com"
              target="_blank"
              rel="noopener noreferrer"
              className="flex items-center gap-2"
              aria-label="Open SnoutiQ website"
            >
              <img
                src={logo}
                alt="SnoutiQ"
                className="h-5 w-auto max-w-[130px] object-contain sm:h-6"
                loading="eager"
                draggable={false}
                onDragStart={(e) => e.preventDefault()}
                onContextMenu={(e) => e.preventDefault()}
              />
              <span className="sr-only">SnoutiQ</span>
            </a>
          </div>

          <div className="flex items-center">
            <Button variant="primary" size="sm" onClick={handleConsultClick} type="button">
              Consult Now
            </Button>
          </div>
        </div>
      </div>
    </nav>
  );
}
