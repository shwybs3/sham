"use client";

import { cn } from "@/lib/cn";

export function Switch({
  checked,
  onChange,
  label,
}: {
  checked: boolean;
  onChange: (value: boolean) => void;
  label?: string;
}) {
  return (
    <button
      type="button"
      role="switch"
      aria-checked={checked}
      onClick={() => onChange(!checked)}
      className="flex items-center gap-3"
    >
      {label && <span className="text-sm font-medium">{label}</span>}
      <span
        dir="ltr"
        className={cn(
          "relative inline-flex h-6 w-11 shrink-0 items-center rounded-full transition-colors",
          checked ? "bg-primary" : "bg-surface-muted"
        )}
      >
        <span
          className={cn(
            "inline-block size-4.5 translate-x-1 transform rounded-full bg-white shadow transition-transform",
            checked && "translate-x-6"
          )}
        />
      </span>
    </button>
  );
}
