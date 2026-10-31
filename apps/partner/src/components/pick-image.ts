import * as ImagePicker from 'expo-image-picker';

/** Opens the camera or the gallery and returns the chosen photo's uri, or null. */
export async function pickImage(source: 'camera' | 'library'): Promise<string | null> {
  const perm =
    source === 'camera'
      ? await ImagePicker.requestCameraPermissionsAsync()
      : await ImagePicker.requestMediaLibraryPermissionsAsync();
  if (!perm.granted) return null;
  const options: ImagePicker.ImagePickerOptions = { mediaTypes: ['images'], quality: 0.6 };
  const res =
    source === 'camera' ? await ImagePicker.launchCameraAsync(options) : await ImagePicker.launchImageLibraryAsync(options);
  return res.canceled ? null : (res.assets[0]?.uri ?? null);
}
