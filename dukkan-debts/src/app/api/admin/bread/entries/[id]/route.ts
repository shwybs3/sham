import { NextRequest, NextResponse } from "next/server";
import { z } from "zod";
import { prisma } from "@/lib/prisma";
import { jsonError, requireAdmin, isErrorResponse } from "@/lib/api-helpers";

const schema = z.object({
  quantity: z.number().int().positive().max(1000).optional(),
  note: z.string().trim().max(200).nullable().optional(),
});

export async function PATCH(request: NextRequest, ctx: RouteContext<"/api/admin/bread/entries/[id]">) {
  const admin = await requireAdmin();
  if (isErrorResponse(admin)) return admin;

  const { id } = await ctx.params;
  const body = await request.json().catch(() => null);
  const parsed = schema.safeParse(body);
  if (!parsed.success) return jsonError(parsed.error.issues[0]?.message ?? "بيانات غير صالحة");

  const entry = await prisma.breadEntry.update({ where: { id }, data: parsed.data });
  return NextResponse.json({ entry });
}

export async function DELETE(_request: NextRequest, ctx: RouteContext<"/api/admin/bread/entries/[id]">) {
  const admin = await requireAdmin();
  if (isErrorResponse(admin)) return admin;

  const { id } = await ctx.params;
  await prisma.breadEntry.delete({ where: { id } });
  return NextResponse.json({ ok: true });
}
