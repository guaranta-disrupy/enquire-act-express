


const UUID = (prefix = 'Q',length = 6) => {
    const timestamp = Date.now().toString().slice(-Math.ceil(length / 2)); // Get half of the ID from timestamp
    const randomPart = Math.random().toString(36).slice(-Math.floor(length / 2)); // Get the rest from random characters
    return prefix+(timestamp + randomPart).slice(0, length); // Ensure exact length
};

const index = (arr,map) =>{
    return arr.length === 0 ? 1 : Math.max(...arr.map((o) => o[map]))+1
}


export default {
    UUID : UUID,
    index : index
} 
