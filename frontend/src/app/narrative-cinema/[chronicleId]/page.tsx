import { redirect } from 'next/navigation';

/** Route legacy — cinema đã dời về (observatory)/chronicle. Xóa hẳn ở P4. */
export default async function LegacyCinemaRedirect({ params }: { params: Promise<{ chronicleId: string }> }) {
  const { chronicleId } = await params;
  redirect(`/chronicle/${chronicleId}`);
}
