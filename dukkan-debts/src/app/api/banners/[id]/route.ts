import { NextRequest, NextResponse } from "next/server";
import { z } from "zod";
import { prisma } from "@/lib/prisma";
import { jsonError, requireAdmin, isErrorResponse } from "@/lib/api-helpers";

const schema = z.object({
  imageUrl: z.string().trim().max(2000).nullable().optional(),
  linkUrl: z.string().trim().max(2000).nullable().optional(),
  text: z.string().trim().max(300).nullable().optional(),
  active: z.boolean().optional(),
  sortOrder: z.number().int().optional(),
});

export async function PATCH(request: NextRequest, ctx: RouteContext<"/api/banners/[id]">) {
  const admin = await requireAdmin();
  if (isErrorResponse(admin)) return admin;

  const { id } = await ctx.params;
  const body = await request.json().catch(() => null);
  const parsed = schema.safeParse(body);
  if (!parsed.success) return jsonError(parsed.error.issues[0]?.message ?? "بيانات غير صالحة");

  const banner = await prisma.banner.update({ where: { id }, data: parsed.data });
  return NextResponse.json({ banner });
}

export async function DELETE(_request: NextRequest, ctx: RouteContext<"/api/banners/[id]">) {
  const admin = await requireAdmin();
  if (isErrorResponse(admin)) return admin;

  const { id } = await ctx.params;
  await prisma.banner.delete({ where: { id } });
  return NextResponse.json({ ok: true });
}
