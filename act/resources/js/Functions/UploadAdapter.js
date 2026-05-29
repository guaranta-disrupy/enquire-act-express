import UploadAdapter from '@/Classes/UploadAdapter'

export default function(editor) {
	editor.plugins.get("FileRepository").createUploadAdapter = (loader) => {
		return new UploadAdapter(loader)
	}
}