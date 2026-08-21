import { Megaphone } from "lucide-react";

export function MarqueeBanner({ text }: { text: string }) {
  return (
    <div className="overflow-hidden border-b border-primary-hover/20 bg-primary text-primary-foreground">
      <div className="flex items-center gap-2 whitespace-nowrap py-2">
        <Megaphone className="mx-3 size-4 shrink-0" />
        <div className="animate-marquee text-sm font-semibold">{text}</div>
      </div>
    </div>
  );
}
