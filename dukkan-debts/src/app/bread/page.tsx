import { getBreadSession } from "@/lib/auth";
import { getSettings, SETTINGS_KEYS } from "@/lib/settings";
import { prisma } from "@/lib/prisma";
import { SiteHeader } from "@/components/site/SiteHeader";
import { SiteFooter } from "@/components/site/SiteFooter";
import { BreadLoginPrompt } from "@/components/bread/BreadLoginPrompt";
import { BreadDashboard } from "@/components/bread/BreadDashboard";

export const dynamic = "force-dynamic";

export default async function BreadPage({ searchParams }: PageProps<"/bread">) {
  const [session, settings, sp] = await Promise.all([getBreadSession(), getSettings(), searchParams]);
  const shopName = settings[SETTINGS_KEYS.shopName];
  const errorRaw = sp.error;
  const error = Array.isArray(errorRaw) ? errorRaw[0] : errorRaw;

  const avatarUrl = session
    ? (await prisma.breadCustomer.findUnique({ where: { id: session.customerId }, select: { avatarUrl: true } }))?.avatarUrl ?? null
    : null;

  return (
    <div className="flex min-h-screen flex-col">
      <SiteHeader shopName={shopName} />
      <main className="flex-1">
        {session ? (
          <BreadDashboard name={session.name} avatarUrl={avatarUrl} />
        ) : (
          <BreadLoginPrompt error={error} />
        )}
      </main>
      <SiteFooter shopName={shopName} />
    </div>
  );
}
