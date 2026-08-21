export function SiteFooter({ shopName }: { shopName: string }) {
  return (
    <footer className="mt-auto border-t border-border py-6 text-center text-sm text-muted">
      © {new Date().getFullYear()} {shopName} — بديل رقمي عصري عن دفتر الديون
    </footer>
  );
}
